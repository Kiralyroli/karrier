<?php

namespace App\Controller\Admin;

use App\Entity\Orders;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class OrdersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Orders::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Rendelés')
            ->setEntityLabelInPlural('Rendelések')
            ->setPageTitle(Crud::PAGE_INDEX, 'Rendelések')
            ->setDefaultSort(['created' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('customId', 'Megrendelés azonosító'),
            TextField::new('firstname', 'Vezetéknév'),
            TextField::new('lastname', 'Keresztnév'),
            TextField::new('email'),
            TextField::new('phone', 'Telefon')->onlyOnDetail(),
            TextField::new('country', 'Ország')->onlyOnDetail(),
            TextField::new('zipcode', 'Irányítószám')->onlyOnDetail(),
            TextField::new('city', 'Város')->onlyOnDetail(),
            TextField::new('address', 'Cím')->onlyOnDetail(),
            ChoiceField::new('packageLevel', 'Csomag szint')
                ->setChoices([
                    'Pályakezdő' => 'beginner',
                    'Junior' => 'junior',
                    'Medior' => 'medior',
                    'Senior' => 'senior',
                    'Vezető' => 'leader',
                ]),
            TextField::new('packageTitle', 'Csomag név'),
            IntegerField::new('packagePrice', 'Csomag ár'),
            IntegerField::new('price', 'Végleges ár'),
            TextField::new('coupon', 'Kupon'),
            DateTimeField::new('created', 'Létrehozva'),
            DateTimeField::new('updated', 'Frissítve'),
            BooleanField::new('success', 'Sikeres fizetés?'),
            ArrayField::new('dataSheet', 'Adatlap')
                ->onlyOnDetail()
                ->setTemplatePath('admin/fields/data_sheet.html.twig'),
            TextField::new('customId', 'Adatlap')->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    return !empty($entity->getDataSheet()) ? '<span class="text-success">Van adatlap</span>' : '<span class="text-danger">Nincs adatlap</span>';
                })
                ->renderAsHtml(),
        ];
    }
}
