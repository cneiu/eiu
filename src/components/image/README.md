图像组件
=========

介绍
--------
这是一种功能强大、健壮的图像处理组件，使用起来很简单。它支持GD、Imagick和Gmagick扩展，以及SVG图像格式。该API是类似于photoshop功能，需要对操作对象进行调用，如果需要的话，可以使用附加的图像处理功能来扩展这些对象。

使用
-----------

### 缩放保存图像

```php
use eiu\components\image\ImageComponent;

/** @var ImageComponent $image */
$image = $this->app->make(ImageComponent::class);
$image->loadGd($file)->resizeToWidth(200)->writeToFile($file);
```

高级
------------

### 使用操作对象

一共六个操作对象，分别是：

* Adjust
* Draw
* Effect
* Filter
* Layer
* Type

有了这些，您就可以在图像上执行高级图像处理操作。如果一个特性还不存在，您可以扩展这些类来添加您自己的自定义特性。

```php
use eiu\components\image\ImageComponent;
use eiu\components\image\Color\Rgb;

/** @var ImageComponent $image */
$img = $this->app->make(ImageComponent::class);

$img = Image::loadImagick('image.jpg');

$img->adjust->brightness(50)->contrast(50);

$img->draw->setFillColor(new Rgb(255, 0, 0))->rectangle(200, 200, 100, 50);

$img->effect->verticalGradient(new Rgb(255, 0, 0), new Rgb(0, 0, 255));

$img->filter->sharpen(10)->swirl(30);

$img->layer->overlay('watermark.png', 200, 200);

$img->type->font('myfont.ttf')->size(24)->xy(50, 100)->text('Hello World!');
```
