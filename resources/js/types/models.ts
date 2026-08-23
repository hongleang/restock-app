export type Shop = {
    id: number;
    name: string;
};

export type Supplier = {
    id: number;
    shop_id: number;
    first_name: string;
    last_name: string;
    contact_name: string | null;
    email: string;
    phone: string;
    lead_time_days: number;
    shop?: Shop;
};

export type Product = {
    id: number;
    shop_id: number;
    supplier_id: number | null;
    name: string;
    sku: string;
    barcode: string;
    category: string;
    cost_price: string;
    sell_price: string;
    current_stock: number;
    reorder_point: number;
    reorder_qty: number;
    shop?: Shop;
    supplier?: Pick<Supplier, 'id' | 'first_name' | 'last_name'> | null;
};

export type StockMovementType = 'sale' | 'restock' | 'adjustment';

export type StockMovement = {
    id: number;
    product_id: number;
    user_id: number | null;
    type: StockMovementType;
    note: string | null;
    created_at: string;
    product?: Pick<Product, 'id' | 'name' | 'shop_id'>;
    user?: { id: number; first_name: string; last_name: string } | null;
};

export type StockMovementTypeOption = {
    value: StockMovementType;
    label: string;
};