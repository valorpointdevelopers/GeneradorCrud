// src/app/productos/productos.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { {Entid} } from './{entidad}.model';

@Injectable({
  providedIn: 'root',
})
export class {Entidades}Service {
  private apiUrl = 'http://localhost:8080/api/{entidades}';

  constructor(private http: HttpClient) {}

  // Obtener todos los productos
  get{Entidad}(): Observable<{Entid}[]> {
    return this.http.get<{Entid}[]>(`${this.apiUrl}/listar{entidades}`);
  }

  // Crear un nuevo producto
  create{Entidad}({entid}: {Entid}): Observable<{Entid}> {
    return this.http.post<{Entid}>(`${this.apiUrl}/crear{entidades}`, {entid});
  }

  // Actualizar un producto existente
  update{Entidad}(item: {Entid}): Observable<{Entid}> {
    return this.http.put<{Entid}>(
      `${this.apiUrl}/actualizar{entidades}/${item.{primaryKey}}`,
      item
    );
  }



  // Eliminar un producto
  delete{Entidad}(id: number): Observable<void> {
    return this.http.delete<void>(
      `${this.apiUrl}/eliminar{entidades}/${id}`
    );
  }
}
