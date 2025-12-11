import { Component, OnInit } from '@angular/core';
import { Chorjuan } from './chorjuan.model';
import { ChorjuanService } from './chorjuan.service';
import { FormsModule } from '@angular/forms';
import { CurrencyPipe, CommonModule } from '@angular/common';

@Component({
  selector: 'app-chorjuan',
  templateUrl: './chorjuan.component.html',
  styleUrls: ['./chorjuan.component.css'],
  standalone: true,
  imports: [FormsModule, CurrencyPipe, CommonModule],
})
export class ChorjuanComponent implements OnInit {

  selectedChorjuan: Chorjuan = this.createEmptyChorjuan();
  chorjuan: Chorjuan[] = [];
  filteredChorjuan: Chorjuan[] = [];
  paginatedChorjuan: Chorjuan[] = [];

  itemsPerPage: number = 5;
currentPage: number = 1;


searchTerm: string = '';

  mostrarModal = false;
  mostrarModalEliminar = false;
  private chorjuanAEliminar: number | null = null;

  constructor(private chorjuanService: ChorjuanService) {}

  ngOnInit() {
    this.loadChorjuan();
  }

  private createEmptyChorjuan(): Chorjuan {
    return {
            idChorjuan: null,   // AUTO PK
      nombre: '',
      fecha: null,
      hora: null,
    };
  }

  abrirModal(item?: Chorjuan) {
    this.selectedChorjuan = item ? { ...item } : this.createEmptyChorjuan();
    this.mostrarModal = true;
  }

  cerrarModal() {
    this.mostrarModal = false;
  }

  saveChorjuan(item: Chorjuan) {
    const request$ = item.idChorjuan
      ? this.chorjuanService.updateChorjuan(item)
      : this.chorjuanService.createChorjuan(item);

    request$.subscribe(() => {
      this.loadChorjuan();
      this.cerrarModal();
    });
  }

  confirmDelete(chorjuanId: number | null | undefined) {
        if (chorjuanId == null) return;
    this.chorjuanAEliminar = chorjuanId;
    this.mostrarModalEliminar = true;
  }

  deleteChorjuanConfirmed() {
    if (this.chorjuanAEliminar !== null) {
      this.chorjuanService.deleteChorjuan(this.chorjuanAEliminar).subscribe(() => {
        this.loadChorjuan();
        this.cancelarEliminar();
      });
    }
  }

  cancelarEliminar() {
    this.mostrarModalEliminar = false;
    this.chorjuanAEliminar = null;
  }

  private loadChorjuan() {
    this.chorjuanService.getChorjuan().subscribe((items: Chorjuan[]) => {
      this.chorjuan = items || [];
      this.applyFilters();
    });
  }

  applyFilters() {
    this.filteredChorjuan = this.searchTerm
    ? this.chorjuan.filter(item =>
        item?.nombre && String(item.nombre).toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        item?.fecha && String(item.fecha).toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        item?.hora && String(item.hora).toLowerCase().includes(this.searchTerm.toLowerCase())
      )
    : [...this.chorjuan];

this.currentPage = 1;
this.updatePagination();
  }

  updatePagination() {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;

    this.paginatedChorjuan = this.filteredChorjuan.slice(startIndex, endIndex);
}

nextPage() {
    if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.updatePagination();
    }
}

previousPage() {
    if (this.currentPage > 1) {
        this.currentPage--;
        this.updatePagination();
    }
}

get totalPages(): number {
    return Math.ceil(this.filteredChorjuan.length / this.itemsPerPage);
}

}
