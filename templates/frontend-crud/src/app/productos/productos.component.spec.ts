import { ComponentFixture, TestBed } from '@angular/core/testing';

import { {Entidades}Component } from './{entidades}.component';

describe('{Entidades}Component', () => {
  let component: {Entidades}Component;
  let fixture: ComponentFixture<{Entidades}Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [{Entidades}Component]
    })
    .compileComponents();

    fixture = TestBed.createComponent({Entidades}Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
