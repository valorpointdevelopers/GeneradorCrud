import { ChorjuanComponent } from './chorjuan/chorjuan.component';

import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },

  // MODULES START
  { path: 'chorjuan', component: ChorjuanComponent },
  // MODULES END

  { path: '**', redirectTo: '/dashboard' }
];
