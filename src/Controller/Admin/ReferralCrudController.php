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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;

#[AdminCrud(routePath: '/mgm-core/referral', routeName: 'mgm_core_referral')]
final class ReferralCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Referral::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('MGM推荐关系')
            ->setEntityLabelInPlural('MGM推荐关系')
            ->setPageTitle('index', 'MGM推荐关系管理')
            ->setPageTitle('detail', 'MGM推荐关系详情')
            ->setPageTitle('new', '创建MGM推荐关系')
            ->setPageTitle('edit', '编辑MGM推荐关系')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM用户间的推荐关系，跟踪推荐状态和时间节点')
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'campaignId', 'referrerType', 'referrerId', 'refereeType', 'refereeId', 'source', 'token'])
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
            ->add(TextFilter::new('campaignId', '活动ID'))
            ->add(TextFilter::new('referrerType', '推荐人类型'))
            ->add(TextFilter::new('referrerId', '推荐人ID'))
            ->add(TextFilter::new('refereeType', '被推荐人类型'))
            ->add(TextFilter::new('refereeId', '被推荐人ID'))
            ->add(TextFilter::new('source', '推荐来源'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('qualifyTime', '资格验证时间'))
            ->add(DateTimeFilter::new('rewardTime', '奖励时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', '推荐关系ID')
            ->setHelp('推荐关系的唯一标识符')
        ;
        $campaignId = TextField::new('campaignId', '活动ID')
            ->setRequired(true)
            ->setHelp('关联的MGM活动ID')
        ;
        $referrerType = TextField::new('referrerType', '推荐人类型')
            ->setRequired(true)
            ->setHelp('推荐人的类型标识')
        ;
        $referrerId = TextField::new('referrerId', '推荐人ID')
            ->setRequired(true)
            ->setHelp('推荐人的唯一标识符')
        ;
        $refereeType = TextField::new('refereeType', '被推荐人类型')
            ->setRequired(true)
            ->setHelp('被推荐人的类型标识')
        ;
        $refereeId = TextField::new('refereeId', '被推荐人ID')
            ->setRequired(true)
            ->setHelp('被推荐人的唯一标识符')
        ;
        $token = TextField::new('token', '归因令牌')
            ->setHelp('用于归因的临时令牌')
        ;
        $source = TextField::new('source', '推荐来源')
            ->setRequired(true)
            ->setHelp('推荐关系的来源渠道')
        ;
        $state = EnumField::new('state', '推荐状态');
        $state->setEnumCases(ReferralState::cases());
        $state->setRequired(true);
        $state->setHelp('推荐关系的当前状态');
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setRequired(true)
            ->setHelp('推荐关系创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
        $qualifyTime = DateTimeField::new('qualifyTime', '资格验证时间')
            ->setHelp('推荐关系通过资格验证的时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
        $rewardTime = DateTimeField::new('rewardTime', '奖励时间')
            ->setHelp('推荐奖励发放的时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $campaignId,
                $referrerType,
                $referrerId,
                $refereeType,
                $refereeId,
                $source,
                $state,
                $createTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $campaignId,
                $referrerType,
                $referrerId,
                $refereeType,
                $refereeId,
                $token,
                $source,
                $state,
                $createTime,
                $qualifyTime,
                $rewardTime,
            ];
        }

        return [
            $id,
            $campaignId,
            $referrerType,
            $referrerId,
            $refereeType,
            $refereeId,
            $token,
            $source,
            $state,
            $createTime,
            $qualifyTime,
            $rewardTime,
        ];
    }
}
