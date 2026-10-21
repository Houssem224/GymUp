import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { CommonModule } from '@angular/common';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';

import { AppComponent } from './app.component';
import { AuthInterceptor } from './core/interceptors/auth.interceptor';
import { routes } from './app.routes';

// Layouts
import { AdminLayoutComponent } from './core/layout/admin-layout/admin-layout.component';
import { MemberLayoutComponent } from './core/layout/member-layout/member-layout.component';
import { CoachLayoutComponent } from './core/layout/coach-layout/coach-layout.component';
import { ReceptionistLayoutComponent } from './core/layout/receptionist-layout/receptionist-layout.component';

// Auth
import { LoginComponent } from './auth/login/login.component';
import { RegisterComponent } from './auth/register/register.component';

@NgModule({
  declarations: [
    AppComponent,
    AdminLayoutComponent,
    MemberLayoutComponent,
    CoachLayoutComponent,
    ReceptionistLayoutComponent,
    LoginComponent,
    RegisterComponent
  ],
  imports: [
    BrowserModule,
    CommonModule,
    HttpClientModule,
    FormsModule,
    ReactiveFormsModule,
    RouterModule.forRoot(routes)
  ],
  providers: [
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true }
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
