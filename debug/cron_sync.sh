#!/bin/bash
# 数据同步定时任务脚本
# 用法:
#   ./cron_sync.sh all        - 运行所有同步任务
#   ./cron_sync.sh calibration - 仅同步校准数据
#   ./cron_sync.sh netvalue    - 仅同步净值数据
#   ./cron_sync.sh fundest     - 仅同步基金估值
#   ./cron_sync.sh future      - 仅同步期货数据
#   ./cron_sync.sh stock       - 仅同步股票历史

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP_BIN="/opt/homebrew/bin/php"
LOG_DIR="$SCRIPT_DIR/logs"
DATE=$(date +%Y-%m-%d)
TIME=$(date +%H:%M:%S)

# 创建日志目录
mkdir -p "$LOG_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_DIR/sync_$DATE.log"
}

run_sync() {
    local script=$1
    local name=$2

    if [ -f "$SCRIPT_DIR/$script" ]; then
        log "开始: $name"
        $PHP_BIN "$SCRIPT_DIR/$script" >> "$LOG_DIR/sync_$DATE.log" 2>&1
        local status=$?
        if [ $status -eq 0 ]; then
            log "完成: $name (成功)"
        else
            log "完成: $name (失败, 退出码: $status)"
        fi
    else
        log "跳过: $script 不存在"
    fi
}

sync_calibration() {
    log "========== 校准数据同步 =========="
    run_sync "sync_calibration.php" "QDII基金校准数据"
    run_sync "sync_calibration2.php" "美股QDII校准数据"
    run_sync "sync_chinafuture_calibration.php" "ChinaFuture校准数据"
    run_sync "sync_fundpair_calibration.php" "基金配对校准数据"
    run_sync "sync_fundlist_calibration.php" "Fundlist校准数据"
}

sync_netvalue() {
    log "========== 净值数据同步 =========="
    run_sync "sync_netvalue.php" "基金净值历史"
}

sync_fundest() {
    log "========== 基金估值同步 =========="
    run_sync "sync_fundest.php" "基金估值数据"
}

sync_future() {
    log "========== 期货数据同步 =========="
    run_sync "sync_future_data.php all" "期货历史数据"
    run_sync "sync_ag0_data.php" "AG0白银数据"
    run_sync "sync_au0_data.php" "AU0黄金数据"
}

sync_stock() {
    log "========== 股票数据同步 =========="
    run_sync "sync_stockhistory.php" "股票价格历史"
    run_sync "sync_sz159937.php" "SZ159937完整数据"
}

sync_all() {
    log "=========================================="
    log "开始全量数据同步"
    log "=========================================="

    sync_calibration
    sleep 2
    sync_netvalue
    sleep 2
    sync_fundest
    sleep 2
    sync_future
    sleep 2
    sync_stock

    log "=========================================="
    log "全量数据同步完成"
    log "=========================================="
}

# 主逻辑
case "${1:-all}" in
    all)
        sync_all
        ;;
    calibration)
        sync_calibration
        ;;
    netvalue)
        sync_netvalue
        ;;
    fundest)
        sync_fundest
        ;;
    future)
        sync_future
        ;;
    stock)
        sync_stock
        ;;
    *)
        echo "用法: $0 {all|calibration|netvalue|fundest|future|stock}"
        exit 1
        ;;
esac
