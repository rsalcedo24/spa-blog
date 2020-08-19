import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute, Params } from '@angular/router';
import { User } from '../../models/user';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
  providers: [UserService]
})
export class LoginComponent implements OnInit {
	public page_title: string;
	public user: User;
	public status: string;
	public token;
	public identity;

	constructor(
		private _userService: UserService,
    private _router: Router,
    private _route: ActivatedRoute
	) { 
		this.page_title = 'Inicia Sesión';
		this.user = new User(1, '', '', 'ROLE_USER', '', '', '', '');
	}

  ngOnInit(): void {
    //ESTO SE EJECUTA SIEMPRE Y CIERRA SESION SOLO CUANDO LE LLEGA EL PARAMETRO SURE POR LA URL
    this.logout();
  }

  	onSubmit(form){
  		this._userService.signup(this.user).subscribe(
  			response => {
  				//TOKEN
  				if(response.status != 'error'){
  					this.status = 'success'; 
  					this.token = response;

  					//OBJETO USUARIO IDENTIFICADO

  					this._userService.signup(this.user, true).subscribe(
			  			response => {
			  				this.identity = response;

			  				//PERSISTIR DATOS DEL USUARIO IDENTIFICADO
			  				console.log(this.token);
			  				console.log(this.identity);

			  				localStorage.setItem('token', this.token);
			  				localStorage.setItem('identity', JSON.stringify(this.identity));

                //REDIRECCION A LA PAGINA PRINCIPAL INICIO
                this._router.navigate(['inicio']);
			  			},
			  			error => {
			  				this.status = 'error';
			  				console.log(<any>error);
			  			}
			  		);

  				}else{
  					this.status = 'error';
  				}
  			},
  			error => {
  				this.status = 'error';
  				console.log(<any>error);
  			}
  		);
  	}

    logout(){
      this._route.params.subscribe(params => {
        let logout = +params['sure'];

        if(logout == 1){
          localStorage.removeItem('identity');
          localStorage.removeItem('token');

          this.identity = null;
          this.token = null;

          //REDIRECCION A LA PAGINA PRINCIPAL INICIO
          this._router.navigate(['inicio']);
        }
      });
    }

}
