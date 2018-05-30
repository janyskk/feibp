Rozšírenie na rozdeľovanie úloh
===============================
Toto rozšírenie je určené pre infomačný systém na správu športových podujatí. Umožňuje priraďovanie používateľov na podujatia podľa kritérií spolu s ďalšími funkciami popísanými v dokumentácií

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist janyskk/feibp "*"
```

or add

```
"janyskk/feibp": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \janyskk\feibp\AutoloadExample::widget(); ?>```