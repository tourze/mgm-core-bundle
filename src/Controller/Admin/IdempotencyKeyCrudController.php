<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;

#[AdminCrud(routePath: '/mgm-core/idempotency-key', routeName: 'mgm_core_idempotency_key')]
final class IdempotencyKeyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return IdempotencyKey::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('幂等性键')
            ->setEntityLabelInPlural('幂等性键')
            ->setPageTitle('index', '幂等性键管理')
            ->setPageTitle('detail', '幂等性键详情')
            ->setPageTitle('new', '创建幂等性键')
            ->setPageTitle('edit', '编辑幂等性键')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM系统的幂等性键，用于防止重复操作')
            ->showEntityActionsInlined()
            ->setSearchFields(['key', 'scope'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('key', '幂等性键'))
            ->add(TextFilter::new('scope', '作用域'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', 'ID')
            ->setHelp('幂等性键的自增主键')
        ;
        $key = TextField::new('key', '幂等性键')
            ->setRequired(true)
            ->setHelp('用于标识操作的唯一键值')
        ;
        $scope = TextField::new('scope', '作用域')
            ->setRequired(true)
            ->setHelp('幂等性键的作用范围')
        ;
        $resultJson = CodeEditorField::new('resultJson', '操作结果')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('操作的结果数据JSON格式')
        ;
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setHelp('幂等性键的创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $key,
                $scope,
                $createTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $key,
                $scope,
                $resultJson,
                $createTime,
            ];
        }

        return [
            $key,
            $scope,
            $resultJson,
        ];
    }
}
