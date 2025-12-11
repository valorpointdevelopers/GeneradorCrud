// src/app/productos/productos.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Chorjuan } from './chorjuan.model';

@Injectable({
  providedIn: 'root',
})
export class ChorjuanService {
  private apiUrl = 'http://localhost:8080/api/chorjuan';

  constructor(private http: HttpClient) {}

  // Obtener todos los productos
  getChorjuan(): Observable<Chorjuan[]> {
    return this.http.get<Chorjuan[]>(`${this.apiUrl}/listarchorjuan`);
  }

  // Crear un nuevo producto
  createChorjuan(chorjuan: Chorjuan): Observable<Chorjuan> {
    return this.http.post<Chorjuan>(`${this.apiUrl}/crearchorjuan`, chorjuan);
  }

  // Actualizar un producto existente
  updateChorjuan(item: Chorjuan): Observable<Chorjuan> {
    return this.http.put<Chorjuan>(
      `${this.apiUrl}/actualizarchorjuan/${item.idChorjuan}`,
      item
    );
  }



  // Eliminar un producto
  deleteChorjuan(id: number): Observable<void> {
    return this.http.delete<void>(
      `${this.apiUrl}/eliminarchorjuan/${id}`
    );
  }
}
