# Upgrade Notes

### Update from Version 1.1.4 to Version 1.2

- Update the [static route](install/staticroutes.xml)!
- Re-import classes from `install/object/structures`!
- use Carbon Date (instead of `$news->getDate()->get('dd.MM.YYYY');` use `$news->getDate()->format('d.m.Y');`)
- Do not use the url helper for creating news detail urls. just use the view helper instead:

```
$url = $this->newsHelper()->getDetailUrl( $this->news );
```
