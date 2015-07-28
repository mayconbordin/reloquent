# reloquent

Verbose Repository Data Access Layer for Laravel 5.

## Yet Another Repository package?

If you search within Github you [can find](https://github.com/search?l=PHP&q=repository+laravel&ref=searchresults&type=Repositories&utf8=%E2%9C%93) 
at least a dozen implementations of the repository pattern for Laravel 4 and 5. For Laravel 5, specifically, the best ones are [prettus/l5-repository](https://github.com/prettus/l5-repository) and [Bosnadev/Repositories](https://github.com/Bosnadev/Repositories).

The difference between these packages and this one is a more flexible interface, enabling more complex queries through the Repository.
See some examples below.

### Examples

Assume we have a `Post` class model with columns: `id`, `title`, `content`, `category_id` and `author_id`.

To find all posts that belong to a single category:

```php
$repository->findAllByCategoryId(1);
```

We can also order the results by `id` in ascending order:

```php
$repository->findAllByCategoryIdOrderById(1);
```

or descending:

```php
$repository->findAllByCategoryIdOrderByDescId(1);
```

We could then limit the number of results returned to 15:

```php
$repository->findAllByCategoryIdOrderByIdLimit(1, 15);
```

Or even paginate the results, with 15 records per page:

```php
$repository->findAllByCategoryIdOrderByIdPaginated(1, 15);
```

We can also get posts within a list of categories:

```php
$repository->findAllByInCategoryId([1, 2, 3, 4]);
```

Or filter the posts by category and author:

```php
$repository->findAllByCategoryIdAndAuthorId(1, 2);
```

Or by category or author:

```php
$repository->findAllByCategoryIdOrAuthorId(1, 2);
```
