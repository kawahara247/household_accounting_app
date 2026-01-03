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

# その他のファイルは644に設定
chmod 644 "$file_path"

exit 0
