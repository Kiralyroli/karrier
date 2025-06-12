<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

#[IsGranted('ROLE_ADMIN')]
class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('code', 'Kuponkód'),
            ChoiceField::new('type', 'Típus')
                ->setChoices([
                    'Százalék' => 'percentage',
                    'Összeg' => 'fixed',
                ]),
            IntegerField::new('value', 'Érték'),
            DateField::new('date_from', 'Érvényes -tól')->setRequired(false),
            DateField::new('date_to', 'Érvényes -ig')->setRequired(false),
            BooleanField::new('status', 'Aktív?'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Kupon')
            ->setEntityLabelInPlural('Kuponok')
            ->setPageTitle(Crud::PAGE_INDEX, 'Kuponok listája')
            ->setPageTitle(Crud::PAGE_NEW, 'Új kupon létrehozása')
            ->setPageTitle(Crud::PAGE_EDIT, 'Kupon szerkesztése');
    }
}
