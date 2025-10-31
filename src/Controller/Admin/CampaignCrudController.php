<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\MgmCoreBundle\Entity\Campaign;
use Tourze\MgmCoreBundle\Enum\Attribution;

#[AdminCrud(routePath: '/mgm-core/campaign', routeName: 'mgm_core_campaign')]
final class CampaignCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Campaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('MGM活动')
            ->setEntityLabelInPlural('MGM活动')
            ->setPageTitle('index', 'MGM活动管理')
            ->setPageTitle('detail', 'MGM活动详情')
            ->setPageTitle('new', '创建MGM活动')
            ->setPageTitle('edit', '编辑MGM活动')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM推荐活动，包括活动配置、归因策略等设置')
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'name'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '活动名称'))
            ->add(BooleanFilter::new('active', '是否激活'))
            ->add(BooleanFilter::new('selfBlock', '禁止自推荐'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', '活动ID')
            ->setHelp('活动的唯一标识符')
        ;
        $name = TextField::new('name', '活动名称')
            ->setRequired(true)
            ->setHelp('活动的显示名称')
        ;
        $active = BooleanField::new('active', '是否激活')
            ->setHelp('控制活动是否可用')
        ;
        $windowDays = IntegerField::new('windowDays', '有效天数')
            ->setRequired(true)
            ->setHelp('推荐关系的有效期天数')
        ;
        $attribution = EnumField::new('attribution', '归因策略');
        $attribution->setEnumCases(Attribution::cases());
        $attribution->setRequired(true);
        $attribution->setHelp('选择推荐归因的策略');
        $selfBlock = BooleanField::new('selfBlock', '禁止自推荐')
            ->setHelp('是否禁止用户推荐自己')
        ;
        $budgetLimit = MoneyField::new('budgetLimit', '预算上限')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('活动的预算上限，留空表示无限制')
        ;
        $configJson = CodeEditorField::new('configJson', '活动配置')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('活动的JSON配置参数')
        ;
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setHelp('活动创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;
        $updateTime = DateTimeField::new('updateTime', '更新时间')
            ->setHelp('活动最后更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $name,
                $active,
                $windowDays,
                $attribution,
                $selfBlock,
                $budgetLimit,
                $createTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $name,
                $active,
                $windowDays,
                $attribution,
                $selfBlock,
                $budgetLimit,
                $configJson,
                $createTime,
                $updateTime,
            ];
        }

        return [
            $id,
            $name,
            $active,
            $windowDays,
            $attribution,
            $selfBlock,
            $budgetLimit,
            $configJson,
        ];
    }
}
