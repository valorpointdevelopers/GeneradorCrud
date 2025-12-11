import { Component, Input } from '@angular/core';
import {NgClass, NgIf} from '@angular/common';
import {RouterLink} from '@angular/router';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  standalone: true,
  imports: [
    NgClass,
    NgIf,
    RouterLink
  ],
  styleUrls: ['app.component.css']
})
export class SidebarComponent {
  @Input() isOpen = false;
}
