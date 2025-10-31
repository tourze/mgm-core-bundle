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
use Tourze\MgmCoreBundle\Entity\Qualification;
use Tourze\MgmCoreBundle\Enum\Decision;

#[AdminCrud(routePath: '/mgm-core/qualification', routeName: 'mgm_core_qualification')]
final class QualificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Qualification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('MGM资格审核')
            ->setEntityLabelInPlural('MGM资格审核')
            ->setPageTitle('index', 'MGM资格审核管理')
            ->setPageTitle('detail', 'MGM资格审核详情')
            ->setPageTitle('new', '创建MGM资格审核')
            ->setPageTitle('edit', '编辑MGM资格审核')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('index', '管理MGM推荐关系的资格审核记录，记录推荐的审核结果')
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'referralId', 'reason'])
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
            ->add(TextFilter::new('reason', '审核原因'))
            ->add(DateTimeFilter::new('occurTime', '事件发生时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', '资格审核ID')
            ->setHelp('资格审核记录的唯一标识符')
        ;
        $referralId = TextField::new('referralId', '推荐关系ID')
            ->setRequired(true)
            ->setHelp('关联的推荐关系ID')
        ;
        $decision = EnumField::new('decision', '审核决定');
        $decision->setEnumCases(Decision::cases());
        $decision->setRequired(true);
        $decision->setHelp('审核的最终决定结果');
        $reason = TextField::new('reason', '审核原因')
            ->setHelp('审核决定的具体原因说明')
        ;
        $evidenceJson = CodeEditorField::new('evidenceJson', '审核证据')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('支持审核决定的证据数据JSON格式')
        ;
        $occurTime = DateTimeField::new('occurTime', '事件发生时间')
            ->setRequired(true)
            ->setHelp('触发审核的事件发生时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
        $createTime = DateTimeField::new('createTime', '创建时间')
            ->setHelp('审核记录创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $referralId,
                $decision,
                $reason,
                $occurTime,
                $createTime,
            ];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $referralId,
                $decision,
                $reason,
                $evidenceJson,
                $occurTime,
                $createTime,
            ];
        }

        return [
            $id,
            $referralId,
            $decision,
            $reason,
            $evidenceJson,
            $occurTime,
        ];
    }
}
