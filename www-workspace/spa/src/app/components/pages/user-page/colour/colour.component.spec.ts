/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { ColourComponent } from './colour.component';

describe('ColourComponent', () => {
  let component: ColourComponent;
  let fixture: ComponentFixture<ColourComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ColourComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ColourComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
