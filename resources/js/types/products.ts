export type Product = {
    id: number;
    name: string;
    sku: string | null;
    description: string | null;
    price: number;
    stock: number;
    isActive: boolean;
};
