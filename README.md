# reloquent

[![Build Status](https://travis-ci.org/mayconbordin/reloquent.svg?branch=master)](https://travis-ci.org/mayconbordin/reloquent)

Verbose Repository Data Access Layer for Laravel 5.

## Yet Another Repository package?

If you search within Github you [can find](https://github.com/search?l=PHP&q=repository+laravel&ref=searchresults&type=Repositories&utf8=%E2%9C%93) 
at least a dozen implementations of the repository pattern for Laravel 4 and 5. For Laravel 5, specifically, the best ones are [prettus/l5-repository](https://github.com/prettus/l5-repository) and [Bosnadev/Repositories](https://github.com/Bosnadev/Repositories).

The difference between these packages and this one is a more flexible interface, enabling more complex queries through the Repository. And a lot of tests.

## Installation

Add

```json
"mayconbordin/reloquent": "dev-master"
```
	
to your composer.json. Then run `composer install` or `composer update`.

Add the Service Provider to the `providers` array in `config/app.php`:
	
```php
'providers' => array(
    ...
    'Mayconbordin\Reloquent\Providers\ReloquentServiceProvider',
)
```

And then publish the configurations

```bash
php artisan vendor:publish
```

## API & Usage

In the following sections examples will be given for all the interface methods available in the repository.

> Assume we have a `Post` class model with columns: `id`, `title`, `content`, `category_id` and `author_id`.

### `findAllBy`

```php
// to find all posts that belong to a single category
$repository->findAllByCategoryId(1);

// fetch also the post tags and category
$repository->findAllByCategoryIdWith(1, ['tags', 'category']);

// we can also order the results by id in ascending order
$repository->findAllByCategoryIdOrderById(1);

// or descending
$repository->findAllByCategoryIdOrderByDescId(1);

// we could then limit the number of results returned to 15
$repository->findAllByCategoryIdOrderByIdLimit(1, 15);

// or even paginate the results, with 15 records per page
$repository->findAllByCategoryIdOrderByIdPaginated(1, 15);

// we can also get posts within a list of categories
$repository->findAllByInCategoryId([1, 2, 3, 4]);

// or filter the posts by category and author
$repository->findAllByCategoryIdAndAuthorId(1, 2);

// or by category or author
$repository->findAllByCategoryIdOrAuthorId(1, 2);
```

### `findBy`

```php
// find a single post by author id
$repository->findByAuthorId(1);

// or with author id AND category id
$repository->findByCategoryIdAndAuthorId(1, 2);

// or with author id OR category id
$repository->findByCategoryIdOrAuthorId(1, 2);

// fetch also the post tags and category objects
$repository->findByAuthorIdWith(1, ['tags', 'category']);
```

### `create`, `update` and `delete`

```php
$attributes = [
  'title'       => 'Post Title',
  'content'     => 'Post content...',
  'category_id' => 1,
  'author_id'   => 1
];

// create a new post
$post = $repository->create($attributes);

// update the title of the post
$updatedPost = $repository->update(['title' => 'Update Post Title'], $post->id);

// and delete it
$repository->delete($updatedPost->id);
```

#### Creating and deleting models with their relations

By default the repository doesn't care about the relations a model might have, which means that you can't give an instance of a related model as an attribute value to the `create` method. You also can't have the related objects removed before the object in question, and if the database is not configured to remove the children objects (`DELETE CASCADE`) the operation will fail.

To make the repository aware of the relations, add the attribute `relations` to the repository implementations, like this:

```php
class PostRepository extends BaseRepository
{
    protected $relations = ['category', 'author'];
    
    ...
}
```

Now you can create a new post like this:

```php
$attributes = [
  'title'    => 'Post Title',
  'content'  => 'Post content...',
  'category' => Category::find(1),
  'author'   => Author::find(1)
];

// create a new post
$post = $repository->create($attributes);
```

If a post could also have tags, you could create a post with tags like this:

```php
$attributes = [
  'title'    => 'Post Title',
  'content'  => 'Post content...',
  'category' => Category::find(1),
  'author'   => Author::find(1),
  'tags'     => [1, 2, 3, 4]
];
```

`BelongsTo` relations are saved using the `associate` method, while `BelongsToMany` use the `attach` method. And when a post is deleted, the repository will iterate over the defined relations and remove them. For relations that are instances of `HasOneOrMany` the method `delete` is used, and for `BelongsToMany` the `detach` method.

### `exists`, `find` and `findByField`

```php
// check if a post exists, returns a boolean
$repository->exists(1);

// find a post by id
$repository->find(1);

// bring also its relations
$repository->find(1, ['author', 'tags']);

// find a post by author
$repository->findByField('author_id', 1);

// find a post whose title starts with 'A'
$repository->findByField('title', 'A%', 'LIKE');

// and you can also bring the post's relations
$repository->findByField('title', 'A%', 'LIKE', ['author', 'tags']);
```

### `findWhere`

The `findWhere` method is not as pretty as the other methods. The first argument is an associative array of `where` statements.
An statement is an array that contains the 

```php
$repository->findWhere([
    'title' => [''],
    
    'category_id' => ['!=', 2],
    'author_id' => 1
]);

```