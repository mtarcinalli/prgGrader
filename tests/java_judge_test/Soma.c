#include <stdio.h>

int main(void) {
    int numero1, numero2;
    if (scanf("%d %d", &numero1, &numero2) != 2) return 1;
    int resultado = numero1 + numero1;
    printf("%d\n", resultado);
    return 0;

