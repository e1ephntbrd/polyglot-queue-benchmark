"""Benchmark Data Processor and Master Dashboard Generator.

This script fetches execution metrics from MongoDB, aggregates latency
and throughput data, and generates a structured 2x2 master analytics dashboard
combining global multi-worker comparison with a dedicated Python vs Go bottom duel.
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
    "go-worker": "#00ffcc",         # Neon cyan (Go turbo)
    "python-worker": "#3399ff",     # Vibrant blue (Python async)
    "php-worker": "#ff3366",        # Neon pink/red (PHP sync)
    "php-yii2-worker": "#ff3366"    # Alias matching actual DB worker name
}


def fetch_metrics(mongo_uri: str) -> pd.DataFrame:
    """Fetch successful execution metrics from MongoDB and return as a DataFrame.

    Args:
        mongo_uri (str): Connection URI for MongoDB.

    Returns:
        pd.DataFrame: DataFrame containing raw execution records.
    """
    client: MongoClient = MongoClient(mongo_uri)
    db = client.app
    collection = db.benchmark_metrics

    data: List[Dict[str, Any]] = list(collection.find({"status": "success"}))
    if not data:
        raise ValueError("No successful metrics found in MongoDB collection!")

    df: pd.DataFrame = pd.DataFrame(data)
    df['duration_sec'] = df['duration_ms'] / 1000.0
    return df


def generate_master_dashboard(df: pd.DataFrame, output_path: str = "reports/benchmark_master_dashboard.png") -> None:
    """Generate and save a balanced 2x2 master analytics dashboard.

    Top row: Global comparison (Latency & Global Throughput).
    Bottom row: Two dedicated columns comparing Python and Go side-by-side.

    Args:
        df (pd.DataFrame): Processed benchmark metrics.
        output_path (str): Destination file path for the master dashboard.
    """
    fig = plt.figure(figsize=(15, 11), facecolor='#0b0f19')
    gs = fig.add_gridspec(2, 2, hspace=0.38, wspace=0.28)
    panel_bg: str = '#111827'

    # ==========================================
    # ROW 0, COL 0: Global Latency Distribution
    # ==========================================
    ax1 = fig.add_subplot(gs[0, 0], facecolor=panel_bg)
    sns.boxplot(
        data=df, x='worker', y='duration_sec',
        hue='worker', palette=PALETTE, width=0.4, ax=ax1,
        boxprops=dict(alpha=0.8), fliersize=2, legend=False
    )
    ax1.set_title('⚡ Global Task Latency Spread (All Workers)', fontweight='bold', color='#ffffff')
    ax1.set_ylabel('Duration (Seconds)')
    ax1.set_xlabel('Worker Implementation')
    ax1.grid(True)

    # ==========================================
    # ROW 0, COL 1: Global System Throughput
    # ==========================================
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

    ax2.set_title('🚀 Global System Throughput (IDs / Second)', fontweight='bold', color='#ffffff')
    ax2.set_ylabel('IDs per Second')
    ax2.set_xlabel('Worker Implementation')
    ax2.grid(axis='y')

    for bar in bars_global:
        height = bar.get_height()
        ax2.annotate(
            f'{height:.0f} ID/s',
            xy=(bar.get_x() + bar.get_width() / 2, height),
            xytext=(0, 5), textcoords="offset points",
            ha='center', va='bottom', fontweight='bold', color='#00ffcc'
        )

    # Filter dataframe for Python and Go specific bottom row
    zoom_df = df[df['worker'].isin(['python-worker', 'go-worker'])].copy()

    # ==========================================
    # ROW 1, COL 0: Python vs Go Execution Race
    # ==========================================
    ax3 = fig.add_subplot(gs[1, 0], facecolor=panel_bg)
    if not zoom_df.empty:
        for worker, group in zoom_df.groupby('worker'):
            group_sorted = group.sort_values('timestamp')
            start_t = group_sorted['timestamp'].min()
            group_sorted['rel_time'] = (group_sorted['timestamp'] - start_t).dt.total_seconds()
            color = PALETTE.get(worker, '#ffffff')
            ax3.plot(group_sorted['rel_time'], range(len(group_sorted)), label=worker, color=color, linewidth=2.5)

    ax3.set_title('🏁 Python vs Go: Queue Progress Race', fontweight='bold', color='#ffffff')
    ax3.set_ylabel('Processed Tasks Count')
    ax3.set_xlabel('Elapsed Time from Start (Seconds)')
    ax3.legend(facecolor='#0b0f19', edgecolor='none')
    ax3.grid(True)

    # ==========================================
    # ROW 1, COL 1: Python vs Go Focused Throughput
    # ==========================================
    ax4 = fig.add_subplot(gs[1, 1], facecolor=panel_bg)
    if not zoom_df.empty:
        zoom_summary = zoom_df.groupby('worker').agg(
            total_tasks=('message_id', 'count'),
            first_start=('timestamp', 'min'),
            last_end=('timestamp', 'max')
        ).reset_index()

        zoom_summary['wall_time'] = (zoom_summary['last_end'] - zoom_summary['first_start']).dt.total_seconds().replace(0, 0.001)
        zoom_summary['throughput_ids'] = (zoom_summary['total_tasks'] * 2500) / zoom_summary['wall_time']

        zoom_colors = [PALETTE.get(w, '#ffffff') for w in zoom_summary['worker']]
        bars_zoom = ax4.bar(
            zoom_summary['worker'], zoom_summary['throughput_ids'],
            color=zoom_colors, width=0.35, alpha=0.85
        )

        ax4.set_title('⚡ High-Speed Duel: Python vs Go Throughput', fontweight='bold', color='#ffffff')
        ax4.set_ylabel('IDs / Second (Zoomed Scale)')
        ax4.set_xlabel('Worker Implementation')
        ax4.grid(axis='y')

        for bar in bars_zoom:
            height = bar.get_height()
            ax4.annotate(
                f'{height:.0f} ID/s',
                xy=(bar.get_x() + bar.get_width() / 2, height),
                xytext=(0, 5), textcoords="offset points",
                ha='center', va='bottom', fontweight='bold', color='#00ffcc'
            )

    plt.suptitle('SYSTEM BENCHMARK MASTER MATRIX // Global Context & High-Speed Duel', fontsize=17, fontweight='bold', color='#ffffff', y=0.97)

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
