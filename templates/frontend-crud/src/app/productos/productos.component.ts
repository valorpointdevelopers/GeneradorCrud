import { Component, OnInit } from '@angular/core';
import { {Entid} } from './{entidad}.model';
import { {Entidades}Service } from './{entidades}.service';
import { FormsModule } from '@angular/forms';
import { CurrencyPipe, CommonModule } from '@angular/common';

@Component({
  selector: 'app-{entidades}',
  templateUrl: './{entidades}.component.html',
  styleUrls: ['./{entidades}.component.css'],
  standalone: true,
  imports: [FormsModule, CurrencyPipe, CommonModule],
})
export class {Entidades}Component implements OnInit {

  selected{Entid}: {Entid} = this.createEmpty{Entid}();
  {entids}: {Entid}[] = [];
  filtered{Entids}: {Entid}[] = [];
  paginated{Entids}: {Entid}[] = [];

  /* PAGINATION_VARS */

  mostrarModal = false;
  mostrarModalEliminar = false;
  private {entidad}AEliminar: number | null = null;

  constructor(private {entidades}Service: {Entidades}Service) {}

  ngOnInit() {
    this.load{Entids}();
  }

  private createEmpty{Entid}(): {Entid} {
    return {
      {EMPTY_COLUMNS}
    };
  }

  abrirModal(item?: {Entid}) {
    this.selected{Entid} = item ? { ...item } : this.createEmpty{Entid}();
    this.mostrarModal = true;
  }

  cerrarModal() {
    this.mostrarModal = false;
  }

  save{Entidad}(item: {Entid}) {
    const request$ = item.{id}
      ? this.{entidades}Service.update{Entidad}(item)
      : this.{entidades}Service.create{Entidad}(item);

    request$.subscribe(() => {
      this.load{Entids}();
      this.cerrarModal();
    });
  }

  confirmDelete({entid}Id: number) {
    this.{entidad}AEliminar = {entid}Id;
    this.mostrarModalEliminar = true;
  }

  delete{Entid}Confirmed() {
    if (this.{entidad}AEliminar !== null) {
      this.{entidades}Service.delete{Entidad}(this.{entidad}AEliminar).subscribe(() => {
        this.load{Entids}();
        this.cancelarEliminar();
      });
    }
  }

  cancelarEliminar() {
    this.mostrarModalEliminar = false;
    this.{entidad}AEliminar = null;
  }

  private load{Entids}() {
    this.{entidades}Service.get{Entidades}().subscribe((items: {Entid}[]) => {
      this.{entids} = items || [];
      this.applyFilters();
    });
  }

  applyFilters() {
    {FILTER_CODE}
  }

  /* PAGINATION_METHODS */

}
