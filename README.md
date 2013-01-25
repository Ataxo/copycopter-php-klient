Copycopter PHP Client
=====================
Nette Translator library for loading translates from copycopter.

Requirements
------------
[Nette Framework 2.0.7](http://nette.org) or higher. (PHP 5.3 edition)
[Kdyby/Curl](https://github.com/Kdyby/Curl) - Curl wrapper for Nette Framework.

Usage
-----
Place class into libs dir. To load translator we recommend implement class initialization into config.neon and load as context variable into templates.

```config.neon
common:
	parameters:
		copycopterApiKey: yourApiKey
	services:
		translator: Translator(%copycopterApiKey%)

```BasePresenter.php
startup(){
    $this->template->setTranslator($this->context->translator);
}

```php code
t('string to translate')

Copyright
---------
Ondrej Podolinský, 2013, [Ataxo Interactive a.s.](http://www.ataxointeractive.com)

License
-------
LGPL