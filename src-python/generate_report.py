"""Benchmark Data Processor and Master Dashboard Generator.

This script fetches execution metrics from MongoDB and generates a structured
3x2 master analytics dashboard with auto-scaling, logarithmic latency axes,
and non-overlapping labels:
- Row 0: Global Overview (All workers including legacy/sync PHP)
- Row 1: Async/Parallel Core Comparison (Python, Go, PHP Fiber, .NET)
- Row 2: Async Queue Race Dynamics & Scaled Average Latency (Log Scale)
"""

from typing import Dict, Any, List
import os
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
from pymongo import MongoClient

# Apply cyberpunk / dark metallic styling configuration
plt.style.use('dark_background')
plt.rcParams.update({
    'font.family': 'sans-serif',
    'font.size': 10,
    'axes.labelsize': 11,
    'axes.titlesize': 12,
    'figure.titlesize': 16,
    'text.color': '#00ffcc',
    'axes.labelcolor': '#00ffcc',
    'xtick.color': '#88ffee',
    'ytick.color': '#88ffee',
    'grid.color': '#223344',
    'grid.linestyle': '--',
    'grid.alpha': 0.7
})

MONGO_URI: str = os.getenv("MONGO_URI", "mongodb://mongodb:27017")

PALETTE: Dict[str, str] = {
    "go-worker": "#00ffcc",         # Neon cyan (Go)
    "python-worker": "#3399ff",     # Vibrant blue (Python async)
    "php-worker": "#ff3366",        # Neon pink (PHP sync/legacy)
    "php-yii2-worker": "#ff3366",   # Alias PHP
    "php-fiber-worker": "#ff9900",  # Neon orange (PHP Fiber / Revolt)
    "dotnet-worker": "#9933ff"      # Cyber purple (.NET C#)
}

ASYNC_WORKERS = ['python-worker', 'go-worker', 'php-fiber-worker', 'dotnet-worker']


def fetch_metrics(mongo_uri: str) -> pd.DataFrame:
    """Fetch successful execution metrics from MongoDB and return as a DataFrame."""
    client: MongoClient = MongoClient(mongo_uri)
    db = client.app
    collection = db.benchmark_metrics

    data: List[Dict[str, Any]] = list(collection.find({"status": "success"}))
    if not data:
        raise ValueError("No successful metrics found in MongoDB collection!")

    df: pd.DataFrame = pd.DataFrame(data)
    df['duration_sec'] = df['duration_ms'] / 1000.0
    df['timestamp'] = pd.to_datetime(df['timestamp'])
    return df


def generate_master_dashboard(df: pd.DataFrame, output_path: str = "reports/benchmark_master_dashboard.png") -> None:
    """Generate and save an optimized 3x2 master analytics dashboard."""
    fig = plt.figure(figsize=(16, 17), facecolor='#0b0f19')
    gs = fig.add_gridspec(3, 2, hspace=0.42, wspace=0.28)
    panel_bg: str = '#111827'

    # =========================================================================
    # ROW 0, COL 0: Global Latency Distribution (ALL Workers)
    # =========================================================================
    ax1 = fig.add_subplot(gs[0, 0], facecolor=panel_bg)
    sns.boxplot(
        data=df, x='worker', y='duration_sec',
        hue='worker', palette=PALETTE, width=0.4, ax=ax1,
        boxprops=dict(alpha=0.8), fliersize=2, legend=False
    )
    ax1.set_title('🌐 Global Task Latency Spread (All Workers)', fontweight='bold', color='#ffffff', pad=12)
    ax1.set_ylabel('Duration (Seconds)')
    ax1.set_xlabel('Worker Implementation')
    ax1.tick_params(axis='x', rotation=15)
    ax1.grid(True)

    # =========================================================================
    # ROW 0, COL 1: Global System Throughput (ALL Workers)
    # =========================================================================
    ax2 = fig.add_subplot(gs[0, 1], facecolor=panel_bg)

    global_summary = df.groupby('worker').agg(
        total_tasks=('message_id', 'count'),
        first_start=('timestamp', 'min'),
        last_end=('timestamp', 'max')
    ).reset_index()

    global_summary['wall_time'] = (global_summary['last_end'] - global_summary['first_start']).dt.total_seconds().replace(0, 0.001)
    global_summary['throughput_ids'] = (global_summary['total_tasks'] * 2500) / global_summary['wall_time']

    global_colors = [PALETTE.get(w, '#ffffff') for w in global_summary['worker']]
    bars_global = ax2.bar(
        global_summary['worker'], global_summary['throughput_ids'],
        color=global_colors, width=0.4, alpha=0.85
    )

    ax2.set_title('🚀 Global Throughput (All Workers)', fontweight='bold', color='#ffffff', pad=12)
    ax2.set_ylabel('IDs per Second')
    ax2.set_xlabel('Worker Implementation')
    ax2.tick_params(axis='x', rotation=15)
    ax2.grid(axis='y')

    max_global_tp = global_summary['throughput_ids'].max()
    ax2.set_ylim(0, max_global_tp * 1.18)

    for bar in bars_global:
        height = bar.get_height()
        ax2.annotate(
            f'{height:.0f} ID/s',
            xy=(bar.get_x() + bar.get_width() / 2, height),
            xytext=(0, 5), textcoords="offset points",
            ha='center', va='bottom', fontweight='bold', color='#00ffcc'
        )

    # Фільтруємо датафрейм виключно для асинхронних воркерів
    async_df = df[df['worker'].isin(ASYNC_WORKERS)].copy()

    # =========================================================================
    # ROW 1, COL 0: Latency Distribution (ONLY Async/Parallel Workers)
    # =========================================================================
    ax3 = fig.add_subplot(gs[1, 0], facecolor=panel_bg)
    if not async_df.empty:
        sns.boxplot(
            data=async_df, x='worker', y='duration_sec',
            hue='worker', palette=PALETTE, width=0.4, ax=ax3,
            boxprops=dict(alpha=0.8), fliersize=2, legend=False
        )
    ax3.set_title('⚡ High-Speed Latency Spread (Async Group Only)', fontweight='bold', color='#ffffff', pad=12)
    ax3.set_ylabel('Duration (Seconds)')
    ax3.set_xlabel('Async Worker Implementation')
    ax3.tick_params(axis='x', rotation=15)
    ax3.grid(True)

    # =========================================================================
    # ROW 1, COL 1: Direct Throughput Comparison (ONLY Async Workers)
    # =========================================================================
    ax4 = fig.add_subplot(gs[1, 1], facecolor=panel_bg)
    if not async_df.empty:
        async_summary = async_df.groupby('worker').agg(
            total_tasks=('message_id', 'count'),
            first_start=('timestamp', 'min'),
            last_end=('timestamp', 'max')
        ).reset_index()

        async_summary['wall_time'] = (async_summary['last_end'] - async_summary['first_start']).dt.total_seconds().replace(0, 0.001)
        async_summary['throughput_ids'] = (async_summary['total_tasks'] * 2500) / async_summary['wall_time']

        async_colors = [PALETTE.get(w, '#ffffff') for w in async_summary['worker']]
        bars_async = ax4.bar(
            async_summary['worker'], async_summary['throughput_ids'],
            color=async_colors, width=0.4, alpha=0.85
        )

        ax4.set_title('⚡ High-Speed Throughput Duel (Async Group Only)', fontweight='bold', color='#ffffff', pad=12)
        ax4.set_ylabel('IDs / Second')
        ax4.set_xlabel('Async Worker Implementation')
        ax4.tick_params(axis='x', rotation=15)
        ax4.grid(axis='y')

        max_async_tp = async_summary['throughput_ids'].max()
        ax4.set_ylim(0, max_async_tp * 1.18)

        for bar in bars_async:
            height = bar.get_height()
            ax4.annotate(
                f'{height:.0f} ID/s',
                xy=(bar.get_x() + bar.get_width() / 2, height),
                xytext=(0, 5), textcoords="offset points",
                ha='center', va='bottom', fontweight='bold', color='#00ffcc'
            )

    # =========================================================================
    # ROW 2, COL 0: Execution Progress Race (ONLY Async Workers)
    # =========================================================================
    ax5 = fig.add_subplot(gs[2, 0], facecolor=panel_bg)
    if not async_df.empty:
        for worker, group in async_df.groupby('worker'):
            group_sorted = group.sort_values('timestamp')
            start_t = group_sorted['timestamp'].min()
            group_sorted['rel_time'] = (group_sorted['timestamp'] - start_t).dt.total_seconds()
            color = PALETTE.get(worker, '#ffffff')
            ax5.plot(group_sorted['rel_time'], range(len(group_sorted)), label=worker, color=color, linewidth=2.5)

    ax5.set_title('🏁 Async Queue Progress Race (Timeline)', fontweight='bold', color='#ffffff', pad=12)
    ax5.set_ylabel('Processed Tasks Count')
    ax5.set_xlabel('Elapsed Time from Start (Seconds)')
    ax5.legend(facecolor='#0b0f19', edgecolor='none')
    ax5.grid(True)

    # =========================================================================
    # ROW 2, COL 1: Avg Message Processing Time (Log Scale for Fair View)
    # =========================================================================
    ax6 = fig.add_subplot(gs[2, 1], facecolor=panel_bg)
    if not async_df.empty:
        avg_duration = async_df.groupby('worker')['duration_ms'].mean().reset_index()
        avg_colors = [PALETTE.get(w, '#ffffff') for w in avg_duration['worker']]
        bars_avg = ax6.bar(
            avg_duration['worker'], avg_duration['duration_ms'],
            color=avg_colors, width=0.4, alpha=0.85
        )

        ax6.set_yscale('log')  # Логарифмічна шкала для наочності розриву
        ax6.set_title('⏱️ Avg Message Processing Time (Log Scale, Lower is Better)', fontweight='bold', color='#ffffff', pad=12)
        ax6.set_ylabel('Duration (ms, Log Scale)')
        ax6.set_xlabel('Async Worker Implementation')
        ax6.tick_params(axis='x', rotation=15)
        ax6.grid(True, which="both", ls="--", alpha=0.3)

        max_avg_dur = avg_duration['duration_ms'].max()
        ax6.set_ylim(bottom=100, top=max_avg_dur * 3.5)

        for bar in bars_avg:
            height = bar.get_height()
            ax6.annotate(
                f'{height:.1f} ms',
                xy=(bar.get_x() + bar.get_width() / 2, height),
                xytext=(0, 6), textcoords="offset points",
                ha='center', va='bottom', fontweight='bold', color='#00ffcc'
            )

    plt.suptitle('SYSTEM BENCHMARK MASTER MATRIX // Global vs Async Core Performance', fontsize=18, fontweight='bold', color='#ffffff', y=0.995)

    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    plt.savefig(output_path, dpi=300, facecolor=fig.get_facecolor(), edgecolor='none')
    print(f"[SUCCESS] Dashboard successfully generated at: {output_path}")


if __name__ == "__main__":
    try:
        metrics_df = fetch_metrics(MONGO_URI)
        generate_master_dashboard(metrics_df)
    except Exception as e:
        print(f"[ERROR] Failed to generate benchmark report: {e}")
        exit(1)

