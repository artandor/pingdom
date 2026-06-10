<?php

namespace App\Controller\Admin;

use App\Entity\Website;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WebsiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Website::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('domain'),
            IntegerField::new('status')->hideOnForm(),
            NumberField::new('responseTime')->hideOnForm()
                ->formatValue(function ($value) {
                    return $value . " ms";
                }),
            ArrayField::new('mailingList'),
            DateTimeField::new('updatedAt')->hideOnForm(),
            TextField::new('redirectTo'),
//            BooleanField::new('redirectionOk')->hideOnForm(),
            DateTimeField::new('lastAlertSent')->hideOnForm(),
            DateTimeField::new('lastOkStatus')->hideOnForm(),
            IntegerField::new('consecutiveFailAmount')->hideOnForm(),
        ];
    }
}
