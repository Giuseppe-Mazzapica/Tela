<?php namespace GM\Tela;

class JsManager implements JsManagerInterface {

    private $nonces;
    private $handle;
	private $enabled = FALSE;
    private $script_added = FALSE;
    private $nonces_added = FALSE;

    public function enable() {
		$this->enabled = TRUE;
        add_action( $this->getHook(), [ $this, 'addScript' ] );
        add_action( $this->getHook(), [ $this, 'addNoncesData' ], PHP_INT_MAX );
    }
	
	public function enabled() {
        return $this->enabled;
    }
	
	public function addNonces( Array $nonces = [] ) {
		if ( ! empty( $nonces ) ) {
        	$this->nonces = array_filter( array_merge( (array) $this->nonces, $nonces ) );
		}
    }

    public function getNonces() {
        return $this->nonces;
    }

    public function addScript() {
        if ( $this->script_added ) {
            return;
        }
        $this->script_added = TRUE;
        $min = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';
        $file = "js/tela_ajax{$min}.js";
        $base = dirname( dirname( __FILE__ ) );
        $args = [
            $this->getHandle(),
            $this->getScriptUrl( $base, $file ),
            [ 'jquery' ],
            $this->getScriptVer( $base, $file ),
            TRUE
        ];
        call_user_func_array( 'wp_enqueue_script', $args );
        wp_localize_script( $this->getHandle(), 'TelaAjaxData', $this->getScriptData() );
    }

    public function addNoncesData() {
        if ( $this->nonces_added || ! $this->script_added ) {
            return;
        }
        $this->nonces_added = TRUE;
        $nonces = $this->getNonces();
        if ( ! empty( $nonces ) ) {
            wp_localize_script( $this->getHandle(), "TelaAjaxNonces", [ 'nonces' => $nonces ] );
        }
    }

    public function getHook() {
        return is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
    }

    public function getHandle() {
        if ( is_null( $this->handle ) ) {
            $this->handle = uniqid( 'tela_ajax_js' );
        }
        return $this->handle;
    }

    private function getScriptUrl( $base, $relative ) {
        return plugins_url( $relative, $base );
    }

    private function getScriptVer( $base, $relative ) {
        $ver = NULL;
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $ver = @filemtime( plugin_dir_path( $base ) . $relative ) ? : uniqid();
        }
        return $ver;
    }

    private function getScriptData() {
        $url_args = [
            'telaajax' => '1',
            'action'   => 'telaajax_proxy',
            'bid'      => get_current_blog_id()
        ];
        $data = [
            'url'      => add_query_arg( $url_args, admin_url( 'admin-ajax.php' ) ),
            'is_admin' => is_admin() ? '1' : '0'
        ];
        return $data;
    }

}