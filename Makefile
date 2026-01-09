.PHONY: push check test fix analyse

CHECK_LOG = check-result.txt

# kawahara247アカウントでGitHubにプッシュ
push:
	GITHUB_TOKEN= gh auth switch --user kawahara247
	GITHUB_TOKEN= git push

# コミット前チェック（テスト + フォーマット + 静的解析）
check:
	@echo "╔══════════════════════════════════════════════════════════════╗" > $(CHECK_LOG)
	@echo "║  Check Result - $$(date '+%Y-%m-%d %H:%M:%S')                ║" >> $(CHECK_LOG)
	@echo "╚══════════════════════════════════════════════════════════════╝" >> $(CHECK_LOG)
	@echo "" >> $(CHECK_LOG)
	@echo "──────────────────────────────────────────────────────────────────" >> $(CHECK_LOG)
	@echo "  [1/3] Test" >> $(CHECK_LOG)
	@echo "──────────────────────────────────────────────────────────────────" >> $(CHECK_LOG)
	@docker-compose exec -T app php artisan config:clear --no-ansi -q 2>&1
	@docker-compose exec -T app php artisan test --no-ansi 2>&1 | grep -E "(PASS|FAIL|Tests:|✓|✗)" | tee -a $(CHECK_LOG)
	@echo "" >> $(CHECK_LOG)
	@echo "──────────────────────────────────────────────────────────────────" >> $(CHECK_LOG)
	@echo "  [2/3] Fix (PHP-CS-Fixer)" >> $(CHECK_LOG)
	@echo "──────────────────────────────────────────────────────────────────" >> $(CHECK_LOG)
	@docker-compose exec -T app ./vendor/bin/php-cs-fixer fix --quiet 2>&1; \
	RESULT=$$?; \
	if [ $$RESULT -eq 0 ]; then \
		echo "  [OK] No files changed" | tee -a $(CHECK_LOG); \
	else \
		echo "  [FIXED] Some files were formatted" | tee -a $(CHECK_LOG); \
	fi
	@echo "" >> $(CHECK_LOG)
	@echo "──────────────────────────────────────────────────────────────────" >> $(CHECK_LOG)
	@echo "  [3/3] Analyse (PHPStan)" >> $(CHECK_LOG)
	@echo "──────────────────────────────────────────────────────────────────" >> $(CHECK_LOG)
	@docker-compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress --no-ansi 2>&1 | grep -E "(\[OK\]|\[ERROR\]|ERROR|Line)" | tee -a $(CHECK_LOG)
	@echo "" >> $(CHECK_LOG)
	@echo "══════════════════════════════════════════════════════════════════" >> $(CHECK_LOG)
	@echo "  Check completed!" >> $(CHECK_LOG)
	@echo "══════════════════════════════════════════════════════════════════" >> $(CHECK_LOG)
	@echo ""
	@echo "Results saved to $(CHECK_LOG)"

# テスト実行
test:
	docker-compose exec -T app php artisan config:clear --ansi
	docker-compose exec -T app php artisan test

# コードフォーマット（PHP-CS-Fixer）
fix:
	docker-compose exec -T app ./vendor/bin/php-cs-fixer fix

# 静的解析（PHPStan/Larastan）
analyse:
	docker-compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
