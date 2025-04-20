# Angel Stones

PHP and HTML5 based product catalog and showcase application.

## API Documentation

The application provides the following API endpoints:

### 1. Directory Files API

This API retrieves the list of files and directories from a specified location.

#### Endpoints

##### Get All Product Categories

```
GET get_directory_files.php?directory=products
```

###### Example (curl):
```bash
curl -X GET "http://localhost/newangelstones/get_directory_files.php?directory=products"
```

###### Response:
```json
{
  "success": true,
  "files": [
    {
      "name": "Category1",
      "path": "images/products/Category1",
      "thumbnail": "images/products/Category1/sample.jpg"
    },
    {
      "name": "Category2",
      "path": "images/products/Category2",
      "thumbnail": "images/products/Category2/sample.jpg"
    }
  ]
}
```

##### Get Category Contents

```
GET get_directory_files.php?directory=products/{category_name}
```

###### Example (curl):
```bash
curl -X GET "http://localhost/newangelstones/get_directory_files.php?directory=products/MBNA_2025"
```

###### Response:
```json
{
  "success": true,
  "files": [
    {
      "name": "product1",
      "path": "images/products/MBNA_2025/product1.png",
      "size": 68492,
      "type": "image/png",
      "extension": "png",
      "fullname": "product1.png"
    },
    {
      "name": "product2",
      "path": "images/products/MBNA_2025/product2.png",
      "size": 85236,
      "type": "image/png",
      "extension": "png",
      "fullname": "product2.png"
    }
  ]
}
```

##### Search Products

```
GET get_directory_files.php?search={search_term}
```

###### Example (curl):
```bash
curl -X GET "http://localhost/newangelstones/get_directory_files.php?search=stone"
```

###### Response:
```json
{
  "success": true,
  "files": [
    {
      "name": "bluestone",
      "path": "images/products/Stones/bluestone.jpg",
      "category": "Stones",
      "size": 125600,
      "type": "image/jpeg",
      "extension": "jpg",
      "fullname": "bluestone.jpg"
    }
  ]
}
```

### 2. Image Serving API

This API serves images with cache control to prevent browser caching.

#### Endpoint

```
GET serve_image.php?path={image_path}
```

###### Example (curl):
```bash
curl -X GET "http://localhost/newangelstones/serve_image.php?path=images/products/MBNA_2025/product1.png" --output product1.png
```

## Frontend Features

The application includes the following key features:

1. Thumbnail-first approach:
   - Initially shows thumbnails when a category is opened
   - Clicking on a thumbnail displays the main carousel view
   - Search results also follow the thumbnail-first pattern

2. Horizontally scrollable thumbnails layout:
   - Main image display (65% height) with thumbnails below (35% height)
   - Horizontally scrollable thumbnails with proper styling and active state indication
   - Fullscreen view with navigation controls
   - Mobile-responsive design that adjusts thumbnail sizes based on screen width

3. Image handling:
   - Improved error handling for image loading with fallbacks
   - Special handling for the MBNA_2025 category using PNG files by default
   - Loading indicators and error messages for better user experience

## Browser Support

The application is designed to work with modern browsers including:
- Chrome
- Firefox
- Edge
- Safari
