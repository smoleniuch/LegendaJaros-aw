<?php
namespace App\Services;

use App\Post;

class PostService {

  public function getAllPosts(){

    return Post::all();

  }

}
