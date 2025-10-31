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
use Tourze\MgmCoreBundle\Entity\AttributionToken;

#[AdminCrud(routePath: '/mgm-core/attribution-token', routeName: 'mgm_core_attribution_token')]
final class AttributionTokenCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AttributionToken::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('MGM归因令牌')
            ->setEntityLabelInPlural('MGM归因令牌')
            ->setPageTitle('index', 'MGM归因令牌管理')
            ->setPageTitle('detail', 'MGM归因令牌详情')
            ->setPageTitle('new', '创建MGM归因令牌')
            ->setPageTitle('edit', '编辑MGM归因令牌')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM推荐活动的归因令牌，用于标识推荐关系的临时令牌')
            ->showEntityActionsInlined()
            ->setSearchFields(['token', 'campaignId', 'referrerType', 'referrerId'])
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
            ->add(TextFilter::new('campaignId', '活动ID'))
            ->add(TextFilter::new('referrerType', '推荐人类型'))
            ->add(TextFilter::new('referrerId', '推荐人ID'))
            ->add(DateTimeFilter::new('expireTime', '过期时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $token = IdField::new('token', '归因令牌')
            ->setHelp('唯一的归因令牌标识符')
        ;
        $campaignId = TextField::new('campaignId', '活动ID')
            ->setHelp('关联的MGM活动ID')
        ;
        $referrerType = TextField::new('referrerType', '推荐人类型')
            ->setHelp('推荐人的类型标识')
        ;
        $referrerId = TextField::new('referrerId', '推荐人ID')
            ->setHelp('推荐人的唯一标识符')
        ;
        $expireTime = DateTimeField::new('expireTime', '过期时间')
            ->setHelp('令牌的过期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setHelp('令牌的创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $token,
                $campaignId,
                $referrerType,
                $referrerId,
                $expireTime,
                $createTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $token,
                $campaignId,
                $referrerType,
                $referrerId,
                $expireTime,
                $createTime,
            ];
        }

        return [
            $token,
            $campaignId,
            $referrerType,
            $referrerId,
            $expireTime,
        ];
    }
}
