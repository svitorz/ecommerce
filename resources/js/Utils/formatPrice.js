const formatter = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });

/**
 * Formata um valor monetário em dólar americano, sempre com duas casas.
 * Devolve string vazia quando o valor não é um número.
 */
export function formatPrice(value) {
    const number = Number(value);

    return Number.isFinite(number) ? formatter.format(number) : "";
}
