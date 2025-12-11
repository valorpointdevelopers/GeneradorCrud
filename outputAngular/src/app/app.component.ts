import { ChorjuanComponent } from './chorjuan/chorjuan.component';

import {Component, HostListener} from '@angular/core';
import { RouterOutlet } from '@angular/router';
import {HeaderComponent} from './header.component';
import {SidebarComponent} from './sidebar.component';
import {NgClass} from '@angular/common';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html', // Usaremos un archivo de plantilla HTML
  styleUrls: ['sidebar.component.css'],
  standalone: true,
  imports: [RouterOutlet, HeaderComponent, SidebarComponent, NgClass, ChorjuanComponent] // Importa el componente de productos
})

export class AppComponent {
  title: string | undefined;
  sidebarOpen = false;

  toggleSidebar() {
    this.sidebarOpen = !this.sidebarOpen; // Cambia el estado del sidebar
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event) {
    const sidebar = document.querySelector('.sidebar');
    const toggleButton = document.querySelector('.hamburger-menu'); // Cambia a tu selector de botón

    const isClickInsideSidebar = sidebar?.contains(event.target as Node);
    const isClickOnToggleButton = toggleButton?.contains(event.target as Node);

    if (!isClickInsideSidebar && !isClickOnToggleButton && this.sidebarOpen) {
      this.sidebarOpen = false;
    }
  }
}
