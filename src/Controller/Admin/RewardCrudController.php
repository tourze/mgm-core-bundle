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
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Beneficiary;
use Tourze\MgmCoreBundle\Enum\RewardState;

#[AdminCrud(routePath: '/mgm-core/reward', routeName: 'mgm_core_reward')]
final class RewardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reward::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('MGM奖励')
            ->setEntityLabelInPlural('MGM奖励')
            ->setPageTitle('index', 'MGM奖励管理')
            ->setPageTitle('detail', 'MGM奖励详情')
            ->setPageTitle('new', '创建MGM奖励')
            ->setPageTitle('edit', '编辑MGM奖励')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM推荐活动发放的奖励信息，跟踪奖励状态和发放情况')
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'referralId', 'type', 'externalIssueId', 'idemKey'])
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
            ->add(TextFilter::new('referralId', '推荐关系ID'))
            ->add(TextFilter::new('type', '奖励类型'))
            ->add(TextFilter::new('externalIssueId', '外部发放ID'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('grantTime', '发放时间'))
            ->add(DateTimeFilter::new('revokeTime', '撤销时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', '奖励ID')
            ->setHelp('奖励记录的唯一标识符')
        ;
        $referralId = TextField::new('referralId', '推荐关系ID')
            ->setRequired(true)
            ->setHelp('关联的推荐关系ID')
        ;
        $beneficiary = EnumField::new('beneficiary', '受益人类型');
        $beneficiary->setEnumCases(Beneficiary::cases());
        $beneficiary->setRequired(true);
        $beneficiary->setHelp('奖励的受益人类型');
        $type = TextField::new('type', '奖励类型')
            ->setRequired(true)
            ->setHelp('奖励的具体类型')
        ;
        $specJson = CodeEditorField::new('specJson', '奖励规格')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('奖励的详细规格配置JSON格式')
        ;
        $state = EnumField::new('state', '奖励状态');
        $state->setEnumCases(RewardState::cases());
        $state->setRequired(true);
        $state->setHelp('奖励的当前状态');
        $externalIssueId = TextField::new('externalIssueId', '外部发放ID')
            ->setHelp('外部系统的发放记录ID')
        ;
        $idemKey = TextField::new('idemKey', '幂等性键')
            ->setRequired(true)
            ->setHelp('用于防止重复发放的幂等性键')
        ;
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setRequired(true)
            ->setHelp('奖励记录创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
        $grantTime = DateTimeField::new('grantTime', '发放时间')
            ->setHelp('奖励实际发放时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
        $revokeTime = DateTimeField::new('revokeTime', '撤销时间')
            ->setHelp('奖励撤销时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $referralId,
                $beneficiary,
                $type,
                $state,
                $externalIssueId,
                $createTime,
                $grantTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $referralId,
                $beneficiary,
                $type,
                $specJson,
                $state,
                $externalIssueId,
                $idemKey,
                $createTime,
                $grantTime,
                $revokeTime,
            ];
        }

        return [
            $id,
            $referralId,
            $beneficiary,
            $type,
            $specJson,
            $state,
            $externalIssueId,
            $idemKey,
            $createTime,
            $grantTime,
            $revokeTime,
        ];
    }
}
