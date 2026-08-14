import java.util.Scanner;

public class Soma {
    public static void main(String[] args) {

        Scanner entrada = new Scanner(System.in);

        int numero1 = entrada.nextInt();
        int numero2 = entrada.nextInt();

        int resultado = numero1 + numero2;

        System.out.println(resultado);

        entrada.close();
    }
}
