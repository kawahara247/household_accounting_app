#!/bin/bash

# Claude Code PostToolUse hook: ファイル作成/編集後にパーミッションを設定

# 標準入力からJSONを読み取る
input=$(cat)

# file_pathを抽出
file_path=$(echo "$input" | jq -r '.tool_input.file_path // empty')

# ファイルパスが空または存在しない場合は終了
if [ -z "$file_path" ] || [ ! -f "$file_path" ]; then
    exit 0
fi

# .envファイルは600のまま（機密情報）
if [[ "$file_path" =~ \.env ]]; then
    exit 0
fi

# ファイルは644に設定
chmod 644 "$file_path"

# 親ディレクトリが700の場合は755に設定
parent_dir=$(dirname "$file_path")
if [ -d "$parent_dir" ]; then
    current_perms=$(stat -c "%a" "$parent_dir" 2>/dev/null || stat -f "%OLp" "$parent_dir" 2>/dev/null)
    if [ "$current_perms" = "700" ]; then
        chmod 755 "$parent_dir"
    fi
fi

exit 0
