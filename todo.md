#TODO

       
    
## NOTES 


HOW TO CALCULATE CPD:

```
/**
 * Calculate the Cpd based on the total number of placement units 
 * @param $totalUnits
 * @return int
 */
static function calculateCpd($totalUnits)
{
    $cpd = ($totalUnits * 5);
    return ($cpd > 20) ? 20 : $cpd;
}

...

$message->set('cpd', \cpd\Db\CpdSetup::calculateCpd($obj->totalUnits));
```




