# Pimcore News
Pimcore News Plugin. It's also possible to generate custom types like Press, Blog...

## Requirements
* Pimcore 5. Only with Build 96 or greater.

## Installation

**Composer Installation**  
1. Add code below to your `composer.json`    
2. Activate & install it through backend

```json
"require" : {
    "dachcom-digital/news" : "~2.0.0",
}
```

## Important to know
* The detail url is based on the title for each language. If the detail url field is empty, the title will be transformed to a valid url slug.

## Good to know
* News can be placed at any place on your website through the news area element. Use it as list, latest or even as a custom layout.
* The detail page always stays the same (see static route), no matter where you placed the area element.
* It's possible to override the detail url in the news object.

## Extend News  
**Data**  
* *Meta Information* Tab: Extend News with [classification store](https://www.pimcore.org/docs/latest/Objects/Object_Classes/Data_Types/Classification_Store.html) data.  
* *Relations & Settings* Tab: Extend News with [Object Bricks](https://www.pimcore.org/docs/latest/Objects/Object_Classes/Data_Types/Object_Bricks.html).  

## Events
**news.head.meta**  
TBD
        
**news.detail.url**  
TBD

## Widgets
TBD

## Copyright and license
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)  

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)
