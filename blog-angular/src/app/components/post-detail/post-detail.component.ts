import { User } from './../../models/user';
import { Router, ActivatedRoute, Params } from '@angular/router';
import { PostService } from './../../services/post.service';
import { Post } from './../../models/post';
import { Component, OnInit } from '@angular/core';



@Component({
  selector: 'app-post-detail',
  templateUrl: './post-detail.component.html',
  styleUrls: ['./post-detail.component.css'],
  providers: [PostService]
})
export class PostDetailComponent implements OnInit {
    public post: Post;
    public user: User;

  constructor(
    private _postService: PostService,
    private _route: ActivatedRoute,
    private _router: Router


  ) {

   }

  ngOnInit(): void {
    this.getPost();
  }

  getPost(){
    //SACAR ID DEL POST DE LA URL
    this._route.params.subscribe(params => {
      let id = +params['id'];

      //PETICION AJAX PARA SACAR LOS DATOS
      this._postService.getPost(id).subscribe(
        response => {
          if(response.status == 'success'){
            this.post = response.posts;
            console.log(this.post);
          }else{
            this._router.navigate(['inicio']);
          }
        },
        error => {
          console.log(error);
          this._router.navigate(['inicio']);
          
        }
      );

    }); 

    
  }

}
