import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { HeadphonesModel } from './headphones.model';

@Injectable({
  providedIn: 'root'
})
export class HeadphonesService {
  private apiUrl = 'http://localhost:8080/api/headphones';  // Revisa que coincida con el backend

  constructor(private http: HttpClient) {}


  getHeadphones(): Observable<HeadphonesModel[]> {
    return this.http.get<HeadphonesModel[]>(`${this.apiUrl}/listarheadphones`);
  }

  createHeadphone(headphone: HeadphonesModel): Observable<HeadphonesModel> {
    return this.http.post<HeadphonesModel>(`${this.apiUrl}/crearheadphones`, headphone);
  }

  updateHeadphone(headphone: HeadphonesModel): Observable<HeadphonesModel> {
    return this.http.put<HeadphonesModel>(`${this.apiUrl}/actualizarheadphones/${headphone.id}`, headphone);
  }

  deleteHeadphone(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/eliminarheadphones/${id}`);
  }
}
