<?php

namespace App\Controller\Admin;

use App\Entity\Packages;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PackagesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Packages::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('level', 'Szint')
                ->setChoices([
                    'Pályakezdő' => 'beginner',
                    'Junior' => 'junior',
                    'Medior' => 'medior',
                    'Senior' => 'senior',
                    'Vezető' => 'leader',
                ]),
            TextField::new('title', 'Cím'),
            IntegerField::new('price', 'Ár'),
            IntegerField::new('discount_price', 'Akciós ár'),
            ArrayField::new('features')->setLabel('Funkciók'),
            BooleanField::new('status', 'Aktív?'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Csomag')
            ->setEntityLabelInPlural('Csomagok')
            ->setPageTitle(Crud::PAGE_INDEX, 'Csomagok listája')
            ->setPageTitle(Crud::PAGE_NEW, 'Új csomag létrehozása')
            ->setPageTitle(Crud::PAGE_EDIT, 'Csomag szerkesztése');
    }
}
