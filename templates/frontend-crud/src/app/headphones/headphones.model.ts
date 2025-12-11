// src/app/product.model.ts
export interface HeadphonesModel {
  id: number;
  nombreProducto: string;
  codigoBarras: string;
  cantidad: number;
  descripcion: string;
  precio: number;
  categoria: string;
  proveedor: string;
  enStock: boolean;
}
