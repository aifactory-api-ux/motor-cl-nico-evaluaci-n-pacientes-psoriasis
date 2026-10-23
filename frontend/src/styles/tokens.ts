/**
 * Design tokens del protocolo ALM-PSO-2026.
 *
 * Única fuente de verdad de color, tipografía, espaciado, radios, sombras,
 * iconografía, movimiento y puntos de quiebre. Todos los componentes del
 * frontend importan estos valores en lugar de escribir estilos numéricos,
 * de modo que la interfaz clínica respete el contrato UI/UX aprobado.
 *
 * Los valores provienen del contrato de diseño aprobado y no deben alterarse
 * sin aprobación del diseño.
 */
export const tokens = {
  colors: {
    primary: {
      main: '#1F5C8B',
      hover: '#174A70',
      pressed: '#123B59',
      focus: '#2E86C1',
    },
    secondary: {
      main: '#EAF2F8',
      hover: '#D8E7F1',
      text: '#1F5C8B',
    },
    neutral: {
      white: '#FFFFFF',
      background: '#F5F7FA',
      surface: '#FFFFFF',
      surface_muted: '#EDF1F5',
      border: '#D6DDE5',
      border_strong: '#B8C4D0',
      text_primary: '#17212B',
      text_secondary: '#4A5A6A',
      text_muted: '#6C7A89',
      disabled: '#9AA7B4',
    },
    semantic: {
      success: '#1E7B4F',
      success_surface: '#E8F5EE',
      error: '#B4232A',
      error_surface: '#FBEAEA',
      warning: '#9A6A00',
      warning_surface: '#FFF4D6',
      info: '#1F5C8B',
      info_surface: '#EAF2F8',
    },
    chart: {
      eligible: '#1E7B4F',
      not_eligible: '#B4232A',
      borderline: '#9A6A00',
      series_1: '#1F5C8B',
      series_2: '#5C9BC7',
      series_3: '#A8C6DD',
    },
  },
  typography: {
    font_family: 'Inter, system-ui, -apple-system, sans-serif',
    font_weights: {
      regular: 400,
      medium: 500,
      semibold: 600,
      bold: 700,
    },
    scale: {
      xs: {
        size: '12px',
        line_height: '16px',
        letter_spacing: '0.02em',
      },
      sm: {
        size: '13px',
        line_height: '18px',
        letter_spacing: '0.01em',
      },
      md: {
        size: '14px',
        line_height: '20px',
        letter_spacing: '0',
      },
      lg: {
        size: '16px',
        line_height: '24px',
        letter_spacing: '0',
      },
      xl: {
        size: '18px',
        line_height: '26px',
        letter_spacing: '-0.01em',
      },
      '2xl': {
        size: '22px',
        line_height: '30px',
        letter_spacing: '-0.01em',
      },
      '3xl': {
        size: '28px',
        line_height: '36px',
        letter_spacing: '-0.02em',
      },
    },
  },
  spacing: {
    0: '0px',
    1: '4px',
    2: '8px',
    3: '12px',
    4: '16px',
    5: '20px',
    6: '24px',
    7: '32px',
    8: '40px',
    9: '48px',
    10: '64px',
    11: '80px',
  },
  radii: {
    none: '0px',
    sm: '4px',
    md: '8px',
    lg: '12px',
    xl: '16px',
    pill: '999px',
  },
  shadows: {
    none: 'none',
    sm: '0 1px 2px rgba(23, 33, 43, 0.06)',
    md: '0 4px 12px rgba(23, 33, 43, 0.10)',
    lg: '0 12px 28px rgba(23, 33, 43, 0.14)',
    focus: '0 0 0 3px rgba(46, 134, 193, 0.28)',
  },
  icon_image_style: {
    icon_family: 'Line icons, outline style',
    stroke_width: '1.5px',
    size: '20px por defecto; 16px en tablas y badges',
    corner_style: 'redondeado 2px',
    photo_policy:
      'Sin fotografías reales de pacientes; usar iniciales sobre fondo clínico neutro',
    charts: 'Líneas y barras planas, sin degradados, con leyenda clara',
  },
  motion: {
    duration_fast: '120ms',
    duration_base: '180ms',
    duration_slow: '240ms',
    easing: 'cubic-bezier(0.2, 0, 0.2, 1)',
    prefers_reduced_motion:
      'Reducir animaciones a opacidad/color y eliminar desplazamientos',
    focus_behavior: 'Anillo visible, foco persistente, nunca solo color',
  },
  breakpoints: {
    mobile: '360px',
    tablet: '768px',
    desktop: '1280px',
    wide: '1600px',
  },
};
