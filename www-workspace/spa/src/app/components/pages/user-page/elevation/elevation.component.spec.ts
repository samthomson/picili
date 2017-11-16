/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { ElevationComponent } from './elevation.component';

describe('ElevationComponent', () => {
  let component: ElevationComponent;
  let fixture: ComponentFixture<ElevationComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ElevationComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ElevationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
