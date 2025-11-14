# MGM 核心包

[English](README.md) | [中文](README.zh-CN.md)

一个功能完善的会员推荐（MGM - Member Get Member）系统核心包，专为 Symfony 应用程序设计。本包提供了构建推荐活动、跟踪推荐关系、管理奖励和处理资格认定的核心组件。

## 功能特性

- **活动管理**：创建和管理可配置的推荐活动
- **推荐跟踪**：跟踪推荐关系和归因（首次触达/最后触达）
- **奖励系统**：灵活的奖励分发，支持不同的受益人
- **资格引擎**：确定推荐何时符合奖励条件
- **账本系统**：所有奖励交易的完整审计跟踪
- **幂等支持**：防止重复奖励处理
- **管理界面**：EasyAdmin 集成，用于管理 MGM 数据
- **Doctrine 集成**：完整的 ORM 支持和优化的数据库架构

## 安装

```bash
composer require tourze/mgm-core-bundle
```

## 配置

包会在您的 Symfony 应用程序中自动启用。确保已正确配置 Doctrine：

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            MgmCoreBundle:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/vendor/tourze/mgm-core-bundle/src/Entity'
                prefix: 'Tourze\MgmCoreBundle\Entity'
                alias: MgmCoreBundle
```

## 基本用法

### 创建活动

```php
use Tourze\MgmCoreBundle\Entity\Campaign;

$campaign = new Campaign();
$campaign->setId('summer-2024');
$campaign->setName('夏季推荐活动');
$campaign->setActive(true);

$entityManager->persist($campaign);
$entityManager->flush();
```

### 创建推荐

```php
use Tourze\MgmCoreBundle\Entity\Referral;
use Tourze\MgmCoreBundle\Enum\ReferralState;

$referral = new Referral();
$referral->setCampaign($campaign);
$referral->setReferrerId('推荐人ID');
$referral->setRefereeId('被推荐人ID');
$referral->setState(ReferralState::PENDING);

$entityManager->persist($referral);
$entityManager->flush();
```

### 处理奖励

```php
use Tourze\MgmCoreBundle\Entity\Reward;
use Tourze\MgmCoreBundle\Enum\Decision;
use Tourze\MgmCoreBundle\Enum\Beneficiary;

$reward = new Reward();
$reward->setReferral($referral);
$reward->setDecision(Decision::ACCEPTED);
$reward->setBeneficiary(Beneficiary::REFERRER);
$reward->setAmount(100); // 奖励金额
$reward->setCurrency('CNY');

$entityManager->persist($reward);
$entityManager->flush();
```

## 实体

### 核心实体

- **Campaign**：表示带有配置的推荐活动
- **Referral**：跟踪推荐人与被推荐人之间的关系
- **Reward**：管理奖励分发和决策
- **Qualification**：确定推荐何时符合奖励条件
- **Ledger**：所有财务交易的审计跟踪
- **IdempotencyKey**：防止重复处理
- **AttributionToken**：归因令牌管理

### 数据传输对象（DTO）

- **Subject**：表示推荐系统中的参与者
- **RewardIntent**：分发奖励的意图
- **RewardResult**：奖励处理的结果
- **QualificationResult**：资格检查的结果
- **IssueResult**：奖励发放的结果
- **Evidence**：决策的支持证据

## 枚举

- **ReferralState**：PENDING（待定）、QUALIFIED（合格）、REJECTED（拒绝）、EXPIRED（过期）
- **Attribution**：FIRST（首次触达）、LAST（最后触达）
- **Decision**：PENDING（待定）、ACCEPTED（接受）、REJECTED（拒绝）
- **Beneficiary**：REFERRER（推荐人）、REFEREE（被推荐人）、BOTH（双方）
- **Direction**：INBOUND（入站）、OUTBOUND（出站）
- **RewardState**：PENDING（待定）、ISSUED（已发放）、CANCELLED（已取消）

## 服务类

包提供以下核心服务：

- **ReferralService**：推荐关系管理
- **RewardService**：奖励处理和分发
- **QualificationService**：资格验证
- **LedgerService**：账本管理
- **AttributionService**：归因处理

## 管理后台

包提供用于管理 MGM 数据的 EasyAdmin 控制器：

- 活动管理
- 推荐跟踪
- 奖励审批和分发
- 账本审计
- 资格管理

通过您的 EasyAdmin 仪表板在 `/admin` 访问这些功能。

## API 接口

虽然本包专注于核心功能，但您可以轻松暴露 API 接口：

```php
// 示例控制器
#[Route('/api/referrals')]
class ReferralApiController extends AbstractController
{
    #[Post('/')]
    public function createReferral(Request $request): Response
    {
        // 使用 MgmCoreBundle 服务的实现
    }
}
```

## 事件系统

包提供多个事件用于扩展功能：

- **ReferralCreatedEvent**：推荐创建时触发
- **RewardProcessedEvent**：奖励处理时触发
- **QualificationCompletedEvent**：资格验证完成时触发

## 测试

运行测试套件：

```bash
vendor/bin/phpunit
```

## 数据库架构

包创建以下数据表：

- `mgm_campaigns` - 活动配置
- `mgm_referrals` - 推荐关系
- `mgm_rewards` - 奖励记录
- `mgm_qualifications` - 资格规则和结果
- `mgm_ledger` - 财务审计跟踪
- `mgm_idempotency_keys` - 重复防护
- `mgm_attribution_tokens` - 归因令牌

## 性能优化

- 使用适当的数据库索引优化查询性能
- 支持延迟加载以减少内存使用
- 幂等键机制防止重复处理
- 审计日志采用高效的写入策略

## 安全考虑

- 所有金额字段使用高精度计算
- 支持幂等操作防止重复奖励
- 完整的审计跟踪确保合规性
- 输入验证和类型安全

## 贡献

1. Fork 仓库
2. 创建功能分支
3. 进行更改
4. 为新功能添加测试
5. 运行测试套件
6. 提交 Pull Request

## 开发环境设置

```bash
# 安装依赖
composer install

# 运行测试
vendor/bin/phpunit

# 静态分析
vendor/bin/phpstan

# 修复代码风格
composer fix-cs
```

## 版本兼容性

- PHP 8.1+
- Symfony 6.4+ / 7.0+
- Doctrine ORM 2.14+ / 3.0+

## 许可证

本包使用 MIT 许可证发布。详情请参见 LICENSE 文件。

## 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解变更列表和版本历史。

## 支持

如需报告错误和请求功能，请使用 [问题跟踪器](https://github.com/tourze/mgm-core-bundle/issues)。

## 相关包

- `tourze/doctrine-indexed-bundle` - 数据库索引优化
- `tourze/easy-admin-enum-field-bundle` - 枚举字段支持
- `tourze/easy-admin-menu-bundle` - 菜单管理