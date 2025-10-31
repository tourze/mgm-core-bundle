<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\MgmCoreBundle\Entity\Ledger;
use Tourze\MgmCoreBundle\Enum\Direction;

#[AdminCrud(routePath: '/mgm-core/ledger', routeName: 'mgm_core_ledger')]
final class LedgerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ledger::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('MGM账本记录')
            ->setEntityLabelInPlural('MGM账本记录')
            ->setPageTitle('index', 'MGM账本记录管理')
            ->setPageTitle('detail', 'MGM账本记录详情')
            ->setPageTitle('new', '创建MGM账本记录')
            ->setPageTitle('edit', '编辑MGM账本记录')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM奖励的金额变动记录，跟踪所有奖励相关的资金流水')
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'rewardId', 'currency', 'reason'])
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
            ->add(TextFilter::new('rewardId', '奖励ID'))
            ->add(TextFilter::new('currency', '货币代码'))
            ->add(TextFilter::new('reason', '操作原因'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', '账本记录ID')
            ->setHelp('账本记录的唯一标识符')
        ;
        $rewardId = TextField::new('rewardId', '奖励ID')
            ->setRequired(true)
            ->setHelp('关联的奖励记录ID')
        ;
        $direction = EnumField::new('direction', '金额方向');
        $direction->setEnumCases(Direction::cases());
        $direction->setRequired(true);
        $direction->setHelp('金额变动的方向（加或减）');
        $amount = MoneyField::new('amount', '金额')
            ->setStoredAsCents(false)
            ->setRequired(true)
            ->setHelp('变动的金额数量')
        ;
        $currency = TextField::new('currency', '货币代码')
            ->setRequired(true)
            ->setMaxLength(3)
            ->setHelp('货币的ISO代码，如CNY、USD等')
        ;
        $reason = TextField::new('reason', '操作原因')
            ->setHelp('金额变动的原因说明')
        ;
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setHelp('记录创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $rewardId,
                $direction,
                $amount->setCurrency('CNY'),
                $currency,
                $reason,
                $createTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $rewardId,
                $direction,
                $amount->setCurrency('CNY'),
                $currency,
                $reason,
                $createTime,
            ];
        }

        return [
            $id,
            $rewardId,
            $direction,
            $amount->setCurrency('CNY'),
            $currency,
            $reason,
        ];
    }
}
