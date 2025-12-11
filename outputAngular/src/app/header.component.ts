import { Component, EventEmitter, Output } from '@angular/core';
import { NgClass } from '@angular/common';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  standalone: true,
  imports: [
    NgClass
  ],
  styleUrls: ['header.component.css']
})
export class HeaderComponent {
  @Output() sidebarToggle = new EventEmitter<void>();

  toggleSidebar() {
    this.sidebarToggle.emit(); // Emitir evento para que AppComponent lo reciba
  }
}
