# 贡献指南

感谢你考虑为 pfinal-asyncio-gamekit 做出贡献！

## 如何贡献

### 报告问题

如果你发现了 bug 或有功能建议：

1. 在 [Issues](https://github.com/pfinalclub/pfinal-asyncio-gamekit/issues) 中搜索，确保问题未被报告
2. 创建新的 Issue，使用清晰的标题和详细描述
3. 包含复现步骤、预期行为和实际行为
4. 如果可能，提供最小化的复现代码

### 提交代码

1. **Fork 项目**
   ```bash
   git clone https://github.com/YOUR_USERNAME/pfinal-asyncio-gamekit.git
   cd pfinal-asyncio-gamekit
   ```

2. **创建分支**
   ```bash
   git checkout -b feature/your-feature-name
   # 或
   git checkout -b fix/your-bug-fix
   ```

3. **编写代码**
   - 遵循现有的代码风格
   - 为新功能添加测试
   - 确保所有测试通过
   - 更新相关文档

4. **运行测试**
   ```bash
   composer test
   ```

5. **提交更改**
   ```bash
   git add .
   git commit -m "描述你的更改"
   ```

6. **推送到 GitHub**
   ```bash
   git push origin feature/your-feature-name
   ```

7. **创建 Pull Request**
   - 提供清晰的标题和描述
   - 引用相关的 Issue
   - 等待代码审查

## 代码规范

### PHP 代码风格

- 使用 PSR-12 代码风格
- 使用类型声明（PHP 8.3+）
- 添加 PHPDoc 注释

```php
/**
 * 示例方法
 * 
 * @param string $name 参数说明
 * @return bool 返回值说明
 */
public function exampleMethod(string $name): bool
{
    // 代码实现
}
```

### 测试

- 所有新功能必须包含单元测试
- 测试覆盖率应保持在 80% 以上
- 使用清晰的测试方法名

```php
public function testItCanAddPlayerToRoom(): void
{
    // Arrange
    $room = new TestRoom('room_001');
    $player = new Player('p1', null, 'Alice');
    
    // Act
    $result = $room->addPlayer($player);
    
    // Assert
    $this->assertTrue($result);
}
```

### 提交信息

使用清晰的提交信息：

```
feat: 添加新功能
fix: 修复 bug
docs: 更新文档
test: 添加测试
refactor: 重构代码
style: 代码格式化
chore: 构建/工具更改
```

## 开发环境设置

```bash
# 1. 克隆项目
git clone https://github.com/pfinalclub/pfinal-asyncio-gamekit.git

# 2. 安装依赖
composer install

# 3. 运行测试
composer test

# 4. 运行示例
php examples/SimpleGame.php
```

## 许可证

提交代码即表示你同意将你的贡献以 MIT 许可证发布。

