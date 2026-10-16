import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * 一定の間隔で、サーバーから最新のデータを取り直す(点数などを、自動で更新するため)。
 *
 * - 画面が見えていないとき(別のアプリに切り替えたときなど)は、取得を止める。
 * - 画面に戻ったときは、すぐに 1 回取得する(止まっている間の変更を、取りこぼさないため)。
 *
 * @param only 取り直すデータの名前(Inertia のプロパティ名)
 * @param intervalMs 取得の間隔(ミリ秒)。初期値は 5 秒
 */
export function usePolling(only: string[], intervalMs = 5000): void {
    let timer: ReturnType<typeof setInterval> | null = null;

    // 最新のデータを取り直す(画面のスクロール位置や入力中の内容は、保つ)
    const refresh = (): void => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        router.reload({ only });
    };

    // 画面が見える状態に戻ったら、すぐに取り直す
    const onVisibilityChange = (): void => {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    };

    onMounted(() => {
        timer = setInterval(refresh, intervalMs);
        document.addEventListener('visibilitychange', onVisibilityChange);
    });

    onBeforeUnmount(() => {
        if (timer !== null) {
            clearInterval(timer);
        }

        document.removeEventListener('visibilitychange', onVisibilityChange);
    });
}
