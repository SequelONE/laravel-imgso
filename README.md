# Laravel Imgso
Laravel Imgso пакет обработки изображений для Laravel 4 и 5 на основе библиотеки [PHP Imagine](https://github.com/avalanche123/Imagine). Был вдохновлён разработкой [Croppa](https://github.com/BKWLD/croppa), поскольку он может использовать специально отформатированные URL-адреса для выполнения манипуляций. Он поддерживает базовые манипуляции с изображениями, такие как изменение размера, обрезка, поворот и флип. Он также поддерживает такие эффекты, как негатив, оттенки серого, гамма, расцветка и размытие. Вы также можете определить пользовательские фильтры для большей гибкости.

[![Latest Stable Version](https://poser.pugx.org/sequelone/imgso/v/stable.svg)](https://packagist.org/packages/sequelone/imgso)
[![Build Status](https://travis-ci.org/SequelONE/laravel-imgso.png?branch=master)](https://travis-ci.org/SequelONE/laravel-imgso)
[![Total Downloads](https://poser.pugx.org/sequelone/imgso/downloads.svg)](https://packagist.org/packages/sequelone/imgso)

Основное отличие этого пакета от других библиотек манипуляций с изображениями заключается в том, что вы можете использовать параметры непосредственно в URL-адресе для управления изображением. Управляемая версия изображения сохраняется в том же пути, что и исходное изображение, ** создавая статическую версию файла и обходя PHP для всех будущих запросов **.

Например, если у вас есть изображение по этому URL-адресу:

    /uploads/photo.jpg

Чтобы создать версию 300x300 этого изображения в черно-белом режиме, вы используете URL-адрес:

    /uploads/photo-imgso(300x300-crop-grayscale).jpg
    
Чтобы помочь вам сгенерировать URL-адрес изображения, вы можете использовать метод `Imgso :: url ()`

```php
Imgso::url('/uploads/photo.jpg',300,300,array('crop','grayscale'));
```

или

```html
<img src="<?=Imgso::url('/uploads/photo.jpg',300,300,array('crop','grayscale'))?>" />
```

Альтернативно, вы можете программно манипулировать изображениями с помощью метода `Imgso :: make ()`. Он поддерживает все те же опции, что и метод `Imgso :: url ()`.

```php
Imgso::make('/uploads/photo.jpg',array(
	'width' => 300,
	'height' => 300,
	'grayscale' => true
))->save('/path/to/the/thumbnail.jpg');
```

Или использовать библиотеку Imagine напрямую

```php
$thumbnail = Imgso::open('/uploads/photo.jpg')
			->thumbnail(new Imagine\Imgso\Box(300,300));

$thumbnail->effects()->grayscale();
	
$thumbnail->save('/path/to/the/thumbnail.jpg');
```

## Особенности

Этот пакет использует [Imagine](https://github.com/avalanche123/Imagine) для манипуляции с изображениями. Imagine совместим с GD2, Imagick, Gmagick и поддерживает множество функций (http://imagine.readthedocs.org/en/latest/).

Этот пакет также содержит некоторые общие фильтры, готовые к использованию ([подробнее об этом](https://github.com/SequelONE/laravel-imgso/wiki/Imgso-фильтры)):
- Ресайз изображений
- Обрезка (с положением)
- Вращение
- Черное и белое
- Инвертирование
- Гамма
- Размытие
- Раскрашивание
- Чересстрочная развертка

## Совместимость версий

 Laravel  | Imgso
:---------|:----------
 4.2.x    | 0.1.x
 5.0.x    | 0.2.x
 5.1.x    | 0.3.x
 5.2.x    | 0.3.x
 5.3.x    | 0.3.x

## Установка

#### Зависимости:

* [Laravel 5.x](https://github.com/laravel/laravel)
* [Imagine 0.6.x](https://github.com/avalanche123/Imagine)

#### Требования к серверу:

* [gd](http://php.net/manual/en/book.imgso.php) или [Imagick](http://php.net/manual/fr/book.imagick.php) или [Gmagick](http://www.php.net/manual/fr/book.gmagick.php)
* [exif](http://php.net/manual/en/book.exif.php) - Требуется для получения формата изображения.

#### Установка:

**1-** Добавьте в директорию require вашего файла `composer.json`.
```json
{
	"require": {
		"sequelone/imgso": "0.3.*"
	}
}
```

**2-** Запустите Composer, чтобы установить или обновить новый пакет.

```bash
$ composer install
```

или

```bash
$ composer update
```

**3-** Добавьте поставщика услуг в файл `config/app.php`
```php
'Sequelone\Imgso\ImgsoServiceProvider',
```

**4-** Добавьте фасад `config/app.php`
```php
'Imgso' => 'Sequelone\Imgso\Facades\Imgso',
```

**5-** Публикация файла конфигурации и общих файлов

```bash
$ php artisan vendor:publish --provider="Sequelone\Imgso\ImgsoServiceProvider"
```

**6-** Просмотрите файл конфигурации

```
config/imgso.php
```

## Документация
* [Полная документация](https://github.com/SequelONE/imgso/wiki)
* [Параметры конфигурации](https://github.com/SequelONE/imgso/wiki/Настройка)

## Планы на будущее
Вот некоторые функции, которые мы хотели бы добавить в будущем. Не стесняйтесь сотрудничать и улучшать эту библиотеку.

* Более встроенные фильтры, такие как Яркость и Контраст
* Больше настроек при показе изображений
* Artisan для управления изображениями
* Поддержка пакетных операций над несколькими файлами