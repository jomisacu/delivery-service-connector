create table ds_product_events
(
    id          bigint unsigned auto_increment primary key,
    event_name  varchar(255)                        not null,
    product_id  int                                 not null,
    occurred_on timestamp default CURRENT_TIMESTAMP null,
    hugo_ok     tinyint   default 0                 null
);

create index ds_product_events_event_name_product_id_occurred_on_index
    on ds_product_events (event_name asc, product_id asc, occurred_on desc);

