<?php
declare(strict_types=1);

/**
 * Email Templates
 *
 * Returns HTML email body for a given template key and language.
 *
 * Usage:
 *   require_once __DIR__ . '/email-templates.php';
 *   $html = get_email_template('arc_welcome_1', 'English', ['name' => 'John', 'password' => 'abc123']);
 */

function get_email_template(string $key, string $lang = 'English', array $vars = []): ?array
{
    $name = htmlspecialchars($vars['name'] ?? 'Reader', ENT_QUOTES, 'UTF-8');
    $templates = get_all_templates($name, $vars);

    $template = $templates[$key][$lang] ?? $templates[$key]['English'] ?? null;
    return $template;
}

function get_email_subject(string $key, string $lang = 'English', array $vars = []): string
{
    $template = get_email_template($key, $lang, $vars);
    return $template['subject'] ?? '';
}

function get_email_body(string $key, string $lang = 'English', array $vars = []): string
{
    $template = get_email_template($key, $lang, $vars);
    if ($template === null) {
        return '';
    }
    return wrap_email_html($template['subject'], $template['body']);
}

function get_all_templates(string $name, array $vars): array
{
    $password = $vars['password'] ?? '';
    $campaign_title = htmlspecialchars($vars['campaign_title'] ?? '', ENT_QUOTES, 'UTF-8');
    $deadline = $vars['deadline'] ?? '';
    $tier_name = $vars['tier_name'] ?? '';

    return [
        // -----------------------------------------------------------------
        //  ARC Welcome Sequence
        // -----------------------------------------------------------------
        'arc_welcome_1' => [
            'English' => [
                'subject' => 'Welcome to the ARC Reader Club',
                'body'    => "<h2>Welcome, {$name}!</h2>
                    <p>You have been approved as a member of the ARC Reader Club for Author Juan Jos&eacute;.</p>
                    <p>Your login credentials:</p>
                    <p><strong>Email:</strong> your registered email<br><strong>Temporary password:</strong> {$password}</p>
                    <p>Please log in at <a href='https://authorjuanjose.io/arc-reader-club/login'>authorjuanjose.io/arc-reader-club/login</a> and explore your dashboard.</p>
                    <p><strong>What to expect:</strong> campaign invitations, early access to upcoming releases, recognition milestones, and a growing reader community.</p>
                    <p>Onward through the pages,<br>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => 'Bienvenido al ARC Reader Club',
                'body'    => "<h2>&iexcl;Bienvenido, {$name}!</h2>
                    <p>Has sido aprobado como miembro del ARC Reader Club de Author Juan Jos&eacute;.</p>
                    <p>Tus credenciales de acceso:</p>
                    <p><strong>Email:</strong> tu correo registrado<br><strong>Contrase&ntilde;a temporal:</strong> {$password}</p>
                    <p>Inicia sesi&oacute;n en <a href='https://authorjuanjose.io/arc-reader-club/login'>authorjuanjose.io/arc-reader-club/login</a> y explora tu panel.</p>
                    <p>Adelante entre las p&aacute;ginas,<br>Juan Jos&eacute;</p>",
            ],
        ],

        'arc_welcome_2' => [
            'English' => [
                'subject' => 'How the ARC Reader Club Works',
                'body'    => "<h2>Getting Started, {$name}</h2>
                    <p>Here is how the club works:</p>
                    <ol>
                        <li>Watch for campaign invitations on your dashboard</li>
                        <li>Accept campaigns that interest you</li>
                        <li>Read the ARC within the campaign window</li>
                        <li>Leave an honest review on Amazon or Goodreads</li>
                        <li>Earn distinctions as you participate</li>
                    </ol>
                    <p>Your dashboard is your home base: <a href='https://authorjuanjose.io/arc-reader-club/dashboard'>Visit Dashboard</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => 'C&oacute;mo funciona el ARC Reader Club',
                'body'    => "<h2>Primeros pasos, {$name}</h2>
                    <p>As&iacute; funciona el club:</p>
                    <ol>
                        <li>Busca invitaciones a campa&ntilde;as en tu panel</li>
                        <li>Acepta las campa&ntilde;as que te interesen</li>
                        <li>Lee el ARC dentro del periodo de la campa&ntilde;a</li>
                        <li>Deja una rese&ntilde;a honesta en Amazon o Goodreads</li>
                        <li>Gana distinciones mientras participas</li>
                    </ol>
                    <p>Tu panel es tu base: <a href='https://authorjuanjose.io/arc-reader-club/dashboard'>Visitar Panel</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],

        'arc_welcome_3' => [
            'English' => [
                'subject' => 'Rise Through the Ranks',
                'body'    => "<h2>The Distinction System, {$name}</h2>
                    <p>Active members earn recognition through four tiers:</p>
                    <ul>
                        <li><strong>Tier I &mdash; Copper Cog Commendation</strong> (1 review)</li>
                        <li><strong>Tier II &mdash; Silver Steamwright Honors</strong> (3 reviews)</li>
                        <li><strong>Tier III &mdash; Golden Gearmaster Distinction</strong> (6 reviews)</li>
                        <li><strong>Tier IV &mdash; Obsidian Chrononaut Medal of Honor</strong> (10 reviews)</li>
                    </ul>
                    <p>Track your progress on the <a href='https://authorjuanjose.io/arc-reader-club/my-distinctions'>My Distinctions</a> page.</p>
                    <p>Early members are building the foundation of this community. Thank you for being part of it.</p>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => 'Asciende en los rangos',
                'body'    => "<h2>El Sistema de Distinciones, {$name}</h2>
                    <p>Los miembros activos ganan reconocimiento a trav&eacute;s de cuatro niveles:</p>
                    <ul>
                        <li><strong>Nivel I &mdash; Menci&oacute;n Engranaje de Cobre</strong> (1 rese&ntilde;a)</li>
                        <li><strong>Nivel II &mdash; Honores Steamwright de Plata</strong> (3 rese&ntilde;as)</li>
                        <li><strong>Nivel III &mdash; Distinci&oacute;n Gearmaster Dorado</strong> (6 rese&ntilde;as)</li>
                        <li><strong>Nivel IV &mdash; Medalla de Honor Crononauta Obsidiana</strong> (10 rese&ntilde;as)</li>
                    </ul>
                    <p>Sigue tu progreso en <a href='https://authorjuanjose.io/arc-reader-club/my-distinctions'>Mis Distinciones</a>.</p>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],

        // -----------------------------------------------------------------
        //  Newsletter Welcome Sequence
        // -----------------------------------------------------------------
        'newsletter_welcome_1' => [
            'English' => [
                'subject' => 'Welcome — You\'re In',
                'body'    => "<h2>Welcome, {$name}!</h2>
                    <p>Thank you for subscribing to the Author Juan Jos&eacute; newsletter.</p>
                    <p>Here is what you can expect: new release announcements, behind-the-scenes writing updates, journal entries, and occasional exclusive content.</p>
                    <p>In the meantime, explore the site:</p>
                    <ul>
                        <li><a href='https://authorjuanjose.io/fiction'>Fiction</a> &mdash; Steampunk science fiction</li>
                        <li><a href='https://authorjuanjose.io/non-fiction'>Non-Fiction</a> &mdash; Ideas and insight</li>
                        <li><a href='https://authorjuanjose.io/arc-reader-club'>ARC Reader Club</a> &mdash; Early access and recognition</li>
                    </ul>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => 'Bienvenido — Est&aacute;s dentro',
                'body'    => "<h2>&iexcl;Bienvenido, {$name}!</h2>
                    <p>Gracias por suscribirte al bolet&iacute;n de Author Juan Jos&eacute;.</p>
                    <p>Recibir&aacute;s: anuncios de nuevos lanzamientos, actualizaciones, entradas del diario y contenido exclusivo.</p>
                    <p>Mientras tanto, explora el sitio:</p>
                    <ul>
                        <li><a href='https://authorjuanjose.io/fiction'>Ficci&oacute;n</a></li>
                        <li><a href='https://authorjuanjose.io/non-fiction'>No Ficci&oacute;n</a></li>
                        <li><a href='https://authorjuanjose.io/arc-reader-club'>ARC Reader Club</a></li>
                    </ul>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],

        'newsletter_welcome_2' => [
            'English' => [
                'subject' => 'The Story Behind the Stories',
                'body'    => "<h2>A Little More About the Work, {$name}</h2>
                    <p>Juan Jos&eacute; writes across two lanes: <strong>fiction</strong> (steampunk science fiction) and <strong>non-fiction</strong> (ideas, insight, and real-world thinking).</p>
                    <p>Both come from the same place: a belief that great writing should make you think, make you feel, and leave you changed.</p>
                    <p>Currently in development: an epic eight-part steampunk novella series spanning from the age of steam to space travel.</p>
                    <p><a href='https://authorjuanjose.io/about'>Read more about Juan Jos&eacute;</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => 'La historia detr&aacute;s de las historias',
                'body'    => "<h2>Un poco m&aacute;s sobre el trabajo, {$name}</h2>
                    <p>Juan Jos&eacute; escribe en dos caminos: <strong>ficci&oacute;n</strong> (ciencia ficci&oacute;n steampunk) y <strong>no ficci&oacute;n</strong> (ideas y pensamiento del mundo real).</p>
                    <p>Ambos vienen del mismo lugar: la creencia de que una buena escritura debe hacerte pensar, sentir y dejarte transformado.</p>
                    <p><a href='https://authorjuanjose.io/about'>Lee m&aacute;s sobre Juan Jos&eacute;</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],

        // -----------------------------------------------------------------
        //  Campaign Notifications
        // -----------------------------------------------------------------
        'campaign_invite' => [
            'English' => [
                'subject' => "New ARC Campaign: {$campaign_title}",
                'body'    => "<h2>You Have Been Invited, {$name}!</h2>
                    <p>A new ARC campaign is available: <strong>{$campaign_title}</strong></p>
                    " . ($deadline !== '' ? "<p>Review deadline: <strong>{$deadline}</strong></p>" : '') . "
                    <p>Log in to your dashboard to accept the invitation and receive your advance reader copy.</p>
                    <p><a href='https://authorjuanjose.io/arc-reader-club/current-missions'>View Your Missions</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => "Nueva campa&ntilde;a ARC: {$campaign_title}",
                'body'    => "<h2>&iexcl;Has sido invitado, {$name}!</h2>
                    <p>Una nueva campa&ntilde;a ARC est&aacute; disponible: <strong>{$campaign_title}</strong></p>
                    " . ($deadline !== '' ? "<p>Fecha l&iacute;mite: <strong>{$deadline}</strong></p>" : '') . "
                    <p>Inicia sesi&oacute;n en tu panel para aceptar la invitaci&oacute;n.</p>
                    <p><a href='https://authorjuanjose.io/arc-reader-club/current-missions'>Ver Misiones</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],

        'campaign_reminder' => [
            'English' => [
                'subject' => "Reminder: {$campaign_title} deadline approaching",
                'body'    => "<h2>Friendly Reminder, {$name}</h2>
                    <p>The review deadline for <strong>{$campaign_title}</strong> is approaching" . ($deadline !== '' ? " on <strong>{$deadline}</strong>" : '') . ".</p>
                    <p>If you have finished reading, please submit your review:</p>
                    <p><a href='https://authorjuanjose.io/arc-reader-club/submit-review'>Submit Your Review</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => "Recordatorio: fecha l&iacute;mite de {$campaign_title}",
                'body'    => "<h2>Recordatorio, {$name}</h2>
                    <p>La fecha l&iacute;mite para <strong>{$campaign_title}</strong> se acerca" . ($deadline !== '' ? ": <strong>{$deadline}</strong>" : '') . ".</p>
                    <p><a href='https://authorjuanjose.io/arc-reader-club/submit-review'>Enviar Rese&ntilde;a</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],

        'tier_promotion' => [
            'English' => [
                'subject' => "Congratulations! You earned: {$tier_name}",
                'body'    => "<h2>New Distinction Unlocked, {$name}!</h2>
                    <p>Your dedication has been recognized. You have earned:</p>
                    <p style='font-size:1.3em;font-weight:bold;color:#9d6a2f;'>{$tier_name}</p>
                    <p>View your distinctions: <a href='https://authorjuanjose.io/arc-reader-club/my-distinctions'>My Distinctions</a></p>
                    <p>Thank you for being part of this community.</p>
                    <p>Juan Jos&eacute;</p>",
            ],
            'Spanish' => [
                'subject' => "&iexcl;Felicidades! Has ganado: {$tier_name}",
                'body'    => "<h2>&iexcl;Nueva Distinci&oacute;n, {$name}!</h2>
                    <p>Tu dedicaci&oacute;n ha sido reconocida. Has ganado:</p>
                    <p style='font-size:1.3em;font-weight:bold;color:#9d6a2f;'>{$tier_name}</p>
                    <p><a href='https://authorjuanjose.io/arc-reader-club/my-distinctions'>Mis Distinciones</a></p>
                    <p>Juan Jos&eacute;</p>",
            ],
        ],
    ];
}

function wrap_email_html(string $subject, string $body): string
{
    return "<!DOCTYPE html>
<html lang='en'>
<head><meta charset='utf-8'><meta name='viewport' content='width=device-width'><title>{$subject}</title></head>
<body style='margin:0;padding:0;font-family:Georgia,serif;background:#f5efe2;color:#2b241d;'>
  <div style='max-width:600px;margin:0 auto;padding:2rem;background:#fffaf1;border:1px solid #d8c8ae;'>
    <div style='text-align:center;padding-bottom:1rem;border-bottom:2px solid #d8c8ae;margin-bottom:1.5rem;'>
      <strong style='font-size:1.1rem;color:#2b241d;'>AuthorJuanJose.io</strong>
    </div>
    {$body}
    <div style='margin-top:2rem;padding-top:1rem;border-top:1px solid #d8c8ae;font-size:0.85rem;color:#5c4f3d;text-align:center;'>
      <p>&copy; " . date('Y') . " AuthorJuanJose.io &middot; <a href='https://authorjuanjose.io/privacy' style='color:#6a4520;'>Privacy Policy</a></p>
    </div>
  </div>
</body>
</html>";
}
