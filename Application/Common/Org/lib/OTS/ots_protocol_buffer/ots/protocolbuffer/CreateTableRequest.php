<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.CreateTableRequest)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * CreateTable
 *
 * -*- magic methods -*-
 *
 * @method \ots\protocolbuffer\TableMeta getTableMeta()
 * @method void setTableMeta(\ots\protocolbuffer\TableMeta $value)
 * @method \ots\protocolbuffer\ReservedThroughput getReservedThroughput()
 * @method void setReservedThroughput(\ots\protocolbuffer\ReservedThroughput $value)
 */
class CreateTableRequest extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.CreateTableRequest)
  
  /**
   * @var \ots\protocolbuffer\TableMeta $table_meta
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $table_meta;
  
  /**
   * @var \ots\protocolbuffer\ReservedThroughput $reserved_throughput
   * @tag 2
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $reserved_throughput;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.CreateTableRequest)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.CreateTableRequest)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "table_meta",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\TableMeta',
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "reserved_throughput",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\ReservedThroughput',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.CreateTableRequest)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}