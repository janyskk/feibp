Rozšírenie na rozdeľovanie úloh
===============================
Toto rozšírenie je určené pre infomačný systém na správu športových podujatí. Umožňuje priraďovanie používateľov na podujatia podľa kritérií spolu s ďalšími funkciami popísanými v dokumentácií

Inštalácia
------------

Preferovaný spôsob inštalácie rozšírenia je použitím [composer](http://getcomposer.org/download/).

Buď spustením

```
php composer.phar require --prefer-dist janyskk/feibp "*"
```

alebo 
```
composer require --prefer-dist janyskk/feibp "*"
```

alebo pridaním

```
"janyskk/feibp": "*"
```

do sekcie require vášho `composer.json` súboru.


Použitie
-----

Po inštalácii jednoducho rozšírenie použite vo vašom kóde napr. takto :

```php
<?= \janyskk\feibp\AutoloadExample::widget(); ?>```