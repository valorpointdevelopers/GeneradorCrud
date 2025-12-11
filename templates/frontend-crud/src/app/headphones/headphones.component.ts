import { Component, OnInit } from '@angular/core';
import { HeadphonesModel } from './headphones.model';
import { HeadphonesService } from './headphones.service';

import { FormsModule } from '@angular/forms';
import {CommonModule, CurrencyPipe} from '@angular/common';

@Component({
  selector: 'app-headphones',
  templateUrl: './headphones.component.html',
  styleUrls: ['./headphones.component.css'],
  standalone: true,
  imports: [FormsModule, CurrencyPipe, CommonModule]
})
export class HeadphonesComponent implements OnInit {
  selectedHeadphone: HeadphonesModel = this.createEmptyHeadphone();
  headphones: HeadphonesModel[] = [];
  mostrarModal = false;
  mostrarModalEliminar = false;
  private headphoneAEliminar: number | null = null;

  constructor(private headphonesService: HeadphonesService) {}

  ngOnInit() {
    this.loadHeadphones();
  }

  private createEmptyHeadphone(): HeadphonesModel {
    return {
      id: 0,
      nombreProducto: '',
      codigoBarras: '',
      cantidad: 0,
      descripcion: '',
      precio: 0,
      categoria: '',
      proveedor: '',
      enStock: false
    };
  }

  abrirModal(headphone: HeadphonesModel | null) {
    this.selectedHeadphone = headphone ? { ...headphone } : {
      id: 0,
      nombreProducto: '',
      codigoBarras: '',
      cantidad: 0,
      descripcion: '',
      precio: 0,
      categoria: '',
      proveedor: '',
      enStock: false,
    };
    this.mostrarModal = true;
  }

  cerrarModal() {
    this.mostrarModal = false;
  }

  saveHeadphone(headphone: HeadphonesModel) {
    const request$ = headphone.id
      ? this.headphonesService.updateHeadphone(headphone)
      : this.headphonesService.createHeadphone(headphone);

    request$.subscribe(() => {
      this.loadHeadphones();
      this.cerrarModal();
    });
  }

  confirmDelete(headphoneId: number) {
    this.headphoneAEliminar = headphoneId;
    this.mostrarModalEliminar = true;
  }

  deleteHeadphoneConfirmed() {
    if (this.headphoneAEliminar !== null) {
      this.headphonesService.deleteHeadphone(this.headphoneAEliminar).subscribe(() => {
        this.loadHeadphones();
        this.cancelarEliminar();
      });
    }
  }

  cancelarEliminar() {
    this.mostrarModalEliminar = false;
    this.headphoneAEliminar = null;
  }

  private loadHeadphones() {
    this.headphonesService.getHeadphones().subscribe((headphones: HeadphonesModel[]) => (this.headphones = headphones));
  }
}
