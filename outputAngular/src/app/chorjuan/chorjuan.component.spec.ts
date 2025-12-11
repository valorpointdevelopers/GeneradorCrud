import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChorjuanComponent } from './chorjuan.component';

describe('ChorjuanComponent', () => {
  let component: ChorjuanComponent;
  let fixture: ComponentFixture<ChorjuanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ChorjuanComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChorjuanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
